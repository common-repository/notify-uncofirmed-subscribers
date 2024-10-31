<?php


/*
 * Created on Sep 29, 2007
 * Author: keith
 * Project: Notify Unconfirmed subscribers
 */

require_once ('class.nus.base.php');
require_once ('class.Misc.php');

class nus_feedBurner {

	var $baseURL;
	var $subscriberURL;
	var $loginURL;
	var $cookies;
	var $cookiefile;
	var $errormessage;
	var $myName;
	var $username;
	var $password;
	var $loggedIn;
	var $nosey;
	var $allFeeders = array ();
	var $nextURL;
	var $debug = false;
	var $showemails = false;
	


	function nus_feedBurner($username, $password) {
		$this->myName = 'feedburner';
		$this->username = $username;
		$this->password = $password;
    
		$this->baseURL = 'http://feedburner.google.com/fb/a/myfeeds';
		$this->subscriberURL = 'http://feedburner.google.com/fb/a/emailsyndication?output=report&format=csv&id=';
		$this->loginURL = 'https://www.google.com/accounts/ServiceLoginAuth?service=feedburner';
		$this->loggedIn = false;
		$this->cookiefile = trailingslashit(WP_PLUGIN_DIR).trailingslashit(NUS_COOKIE_DIR)."tmpnuscookie-".NUSMisc::random().".tmp";
		$this->nosey = new nus_Base($this->cookiefile);
	}
  
  function login() {
    if (!$this->username || !$this->password) {
			$this->set_error_message("E100 - Username or password were empty, make sure to enter both before submitting the form");
			$this->loggedIn = false;
			return false;
		}
		
		//load the base URL
		if(!$this->nosey->loadPage($this->baseURL)) {
			$this->set_error_message("E101 - Error Loading FeedBurner Homepage.");
			$this->loggedIn = false;
			return false;
		}
		
		//NUSMisc::debug_dump("login.html", $this->nosey->getResponseBody());
		
		//we should have a redirect location which leads to the login url
		$headers = $this->nosey->getResponseHeaders();
		$this->nextURL = $headers['location'];
		if(!$this->nosey->loadPage($this->nextURL)) {
			$this->set_error_message("E102 - Error Redirecting to Login Page.");
			$this->loggedIn = false;
			return false;
		}
		
		//fetch the varialbles required for posting the auth page
		$response = $this->nosey->getResponseBody();
		//NUSMisc::debug_dump("loginpage.html", $this->nosey->getResponseBody());
		$match_str = "/<input type=\"hidden\" name=\"dsh\"(.*?)value=\"(.*?)\"/is";
		preg_match_all ($match_str, $response, $matcher);
		if($matcher) {
			$dsh = $matcher[2][0];
		}
		$match_str = "/name=\"GALX\"(.*?)value=\"(.*?)\"/is";
		preg_match_all ($match_str, $response, $matcher);
		if($matcher) {
			$galx = $matcher[2][0];
		}
		//can't work without this, throw an error if this is missing
		if(!$galx) {
			$this->set_error_message("E103 - Missing required GALX value.");
			$this->loggedIn = false;
			return false;
		}
		
		//construct the post data array
		$postdata_arr = array(
												"continue" => "http://feedburner.google.com/fb/a/myfeeds",
												"service" => "feedburner",
												"dsh" => $dsh,
												"GALX" => $galx,
												"Email" => $this->username,
												"Passwd" => $this->password,
												"signIn" => "Sign+in",
												"rmShown" => "1",
												"PersistentCookie" => "yes",
												"asts" => "");

		//load the login page here
		if (!$this->nosey->loadPage($this->loginURL, true, $postdata_arr)) {
			$this->set_error_message("E104 - Error occured while authenticating user.");
			$this->loggedIn = false;
			return false;
		}
    if($this->is_login_failure($this->nosey->getResponseBody())) {
      $this->loggedIn = false;
			$this->set_error_message("E403 - Could not validate the username and password, please try again later.");
			return false;
    }
		//NUSMisc::debug_dump("afterlogin.html", $this->nosey->getResponseBody());
		
		$headers = $this->nosey->getResponseHeaders();
		if($headers['location']) {
			$this->nextURL = $headers['location'];
			
			if(!$this->nosey->loadPage($this->nextURL)) {
				$this->set_error_message("E105 - Error occured while setting Google Cookie.");
				$this->loggedIn = false;
				return false;
			}
		}
		
		if($this->is_cookie_failure($this->nosey->getResponseBody())) {
      $this->loggedIn = false;
			$this->set_error_message("E400 - Encountered problems while setting cookies for FeedBurner.");
			return false;
    }
		
		//NUSMisc::print_array($this->nosey->getResponseHeaders());
		//NUSMisc::debug_dump("checkcookie.html", $this->nosey->getResponseBody());
		
		$response = $this->nosey->getResponseBody();
		$response = str_replace("&#39;", "'", $response);
		$response = str_replace("&amp;", "&", $response);
		preg_match('/(.*?)0; url=\'(.*?)\'"/is', $response, $matcher);
		$this->nextURL = $matcher[2];
		if($this->nextURL) {
			if(!$this->nosey->loadPage($this->nextURL)) {
				$this->set_error_message("E106 - Error occured while redirecting to Feedburner home.");
				$this->loggedIn = false;
				return false;
			}
			
			$headers = $this->nosey->getResponseHeaders();
			if($headers['location']) {
				$this->nextURL = $headers['location'];
				if(!$this->nosey->loadPage($this->nextURL)) {
					$this->set_error_message("E107 - Error occured while loading feed listing page.");
					$this->loggedIn = false;
					return false;
				}
			}
		}
		else {
			$this->set_error_message("E108 - Could not find redirect to Feedburner home page.");
			$this->loggedIn = false;
			return false;
		}
		
		//NUSMisc::debug_dump("finallyin.html", $this->nosey->getResponseBody());
		return true;
  }

	function get_unconfirmed_subscribers() {
		if (!$this->nosey->loadPage($this->baseURL)) {
      $this->loggedIn = false;
			return false;
    }
    $feed_data = $this->nosey->getResponseBody();
		//NUSMisc::debug_dump("afterloadads.html", $this->nosey->getResponseBody());
		$feeds = array();
		preg_match_all('#(.*?)fb/a/dashboard\?id=(.*?)"#s', $feed_data, $matcher);
		$the_feeds = $matcher[2];
		$my_feeds = array_unique($the_feeds);
		foreach ($my_feeds as $feed) {
			$feedSubs = $this->subscriberURL . $feed;
      
			preg_match('#<a href="(.*?)fb/a/dashboard\?id='.$feed.'"(.*?)>(.*?)</a>#s', $feed_data, $namer);
			$feedName = $namer[3];
			if ($this->nosey->loadPage($feedSubs)) {
				$feeders = $this->nosey->getResponseBody();
				$feeders = str_replace("Email,Subscribed,Status,", "", $feeders);
				$rows = explode("\n",  $feeders);
				$feederEmails = array();
				foreach($rows as $row) {
					$cols = explode(",", $row);
					if(strcasecmp(trim($cols[2]), 'Pending Verification') == 0) {
						$feederEmails[] = trim($cols[0]);
					}
				}
				$feeds[$feedName]['id'] = $feed;
				$feeds[$feedName]['emails'] = $feederEmails;
			}
		}
		if($this->showemails)
			NUSMisc::print_array($feeds);
		return $feeds;
	}
	
	function set_error_message($errormessage) {
		$this->errormessage = $errormessage;
	}
	
	function print_error() {
		NUSMisc::print_error($this->errormessage);
	}
	
	function add_cookie_to_CURL($handle) {
		//curl_setopt($handle, CURLOPT_COOKIE, $this->cookies_to_string($this->get_cookies()));
		curl_setopt($handle, CURLOPT_COOKIEJAR, $this->cookiefile);
		curl_setopt($handle, CURLOPT_COOKIEFILE, $this->cookiefile);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);
	}
	
	function set_cookies($cookies) {
		$this->cookies = $cookies;
	}
	
	function get_cookies() {
		return $cookies;
	}
	
	function cookies_to_string($cookies) {
		$cookies_header = '';
		foreach ( (array) $cookies as $cookie ) {
			$cookies_header .= $cookie->getHeaderValue() . '; ';
		}
		$cookies_header = substr( $cookies_header, 0, -2 );
		$this->cookies = $cookies;
	}
	
	function delete_cookies() {
		@unlink($this->cookiefile);
	}
	
	function is_login_failure($response) {
		preg_match('/(.*?)The username or password you entered is incorrect(.*?)"/is', $response, $matcher);
    if($matcher) {
			return true;
    }
		preg_match('/(.*?)Enter the letters as they are shown in the image above(.*?)"/is', $response, $matcher);
		if($matcher) {
			return true;
    }
		return false;
	}
	
	function is_cookie_failure($response) {
		preg_match('/(.*?)Clearing cache and cookies(.*?)"/is', $response, $matcher);
    if($matcher) {
			return true;
    }
		return false;
	}
	
}
?>
