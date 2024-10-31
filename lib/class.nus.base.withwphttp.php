<?php


/*
 * Created on Sep 29, 2007
 * Author: keith
 * Project: Notify Unconfirmed subscribers
 *
 * Modified: 5th March, 2010
 */

class nus_Base {

	var $URL;
	var $rawResponse;
	var $responseBody;
	var $responseCode;
	var $responseHeaders;
	var $cookies;
	var $userAgent;
	var $debugMode = false;
	

	var $http_client;
	function nus_Base() {
		$this->http_client = new WP_Http;
		$this->userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6';
		$this->debugMode = false;
	}
	
	function setURL($URL) {
		if($this->debugMode) 
			echo "Loading $URL<br />";
		$this->URL = $URL;
	}

	function getURL() {
		return $this->URL;
	}
	
	function setResponseBody($responseBody) {
		if($this->debugMode) {
			echo "Response Body for ".$this->getURL."<br />";
			echo "<br/>------------------------------------------------------------------------<br/>";
			echo $responseBody;
			echo "<br/>------------------------------------------------------------------------<br/>";
		}
		$this->responseBody = $responseBody;
	}
	
	function getResponseBody() {
		return $this->responseBody;
	}
	
	function getResponseCode() {
		return $this->responseCode;
	}
	
	function setResponseCode($responseCode) {
		if($this->debugMode) {
			echo "Response Code for ".$this->getURL." is $responseCode<br />";
		}
		$this->responseCode = $responseCode;
	}
	
	function getResponseHeaders() {
		return $this->responseHeaders;
	}
	
	function setResponseHeaders($responseHeaders) {
		if($this->debugMode) {
			echo "Response Headers for ".$this->getURL."<br />";
			NUSMisc::print_array($responseHeaders);
		}
		$this->responseHeaders = $responseHeaders;
	}
	
	function getCookies() {
		return $this->cookies;
	}
	
	function setCookies($cookies) {
		if($this->debugMode) {
			echo "Response Cookies for ".$this->getURL."<br />";
			NUSMisc::print_array($cookies);
		}
		$this->cookies = $cookies;
	}
	
	function getRawResponse() {
		return $this->rawResponse;
	}

	function loadPage($URL, $isPost = false, $postData = array(), $headers = array()) {
		$this->resetResponse();
		$this->setURL($URL);
		if(!$this->URL) {
			echo "Wrong method used to call me.";
			return false;
		}
		if($this->getCookies()) {
			$cookies = $this->getCookies();
			$cookies_header = '';
			foreach ( (array) $cookies as $cookie ) {
				$cookies_header .= $cookie->getHeaderValue() . '; ';
			}
			$cookies_header = trim($cookies_header);
			//$cookies_header = substr( $cookies_header, 0, -2 );
		}
		$headers['Cookie'] = $cookies_header;
		$this->cookies = "";
		if(!$isPost) {
			$response = $this->http_client->request($URL, array('user-agent' => $this->userAgent, 'sslverify' => false, 'headers' => $headers));
		}
		else {
			$response = $this->http_client->request( $URL, array('user-agent' => $this->userAgent, 'sslverify' => false, 'method' => 'POST', 'body' => $postData, 'headers' => $headers) );
		}
		$this->rawResponse = $response;
		if($response) {
			$this->setResponseBody($response['body']);
			$this->setResponseCode($response['response']['code']);
			$this->setResponseHeaders($response['headers']);
			$this->setCookies($response['cookies']);
		}
		//return true only if we get a 200 status code
		if($this->responseCode == 200 || $this->responseCode == 302) {
			return true;
		}
		else {
			return false;
		}
	}
	
	function resetResponse() {
		$this->responseBody = "";
		$this->responseCode = "";
		$this->responseHeader = "";
		//$this->cookies = "";
	}

}

?>
