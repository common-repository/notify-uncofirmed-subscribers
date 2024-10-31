<?php


/*
 * Created on Sep 29, 2007
 * Author: keith
 * Project: Notify Unconfirmed subscribers
 *
 * Modified: 5th March, 2010
 * Some part of the Header and Cookie Logic copied from core WP_HTTP class
 * Still using this as this only works with cUrl right now and I need control over redirection
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
	var $cookiefile;

	var $cURL;
	function nus_Base($cookiefile) {
		$this->cookiefile = $cookiefile;
		//$this->init_http_client();
		$this->userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6';
	}
	
	function init_http_client() {
		$this->cURL = curl_init();
		if(!$this->cookiefile) {
			echo "Wrong method used to call me in init.";
			return false;
		}
		$header[] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$header[] = "Pragma: ";
		
		curl_setopt($this->cURL, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->cURL, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->cURL, CURLOPT_USERAGENT, $this->userAgent);
		curl_setopt($this->cURL, CURLOPT_HTTPHEADER, $header);
		curl_setopt($this->cURL, CURLOPT_REFERER, '.$referrer.');
		curl_setopt($this->cURL, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($this->cURL, CURLOPT_AUTOREFERER, true);
    curl_setopt($this->cURL, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($this->cURL, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->cURL, CURLOPT_TIMEOUT, 10);
		curl_setopt($this->cURL, CURLOPT_MAXREDIRS, 5);
		curl_setopt($this->cURL, CURLOPT_COOKIEJAR, $this->cookiefile);
		curl_setopt($this->cURL, CURLOPT_COOKIEFILE, $this->cookiefile);
		curl_setopt($this->cURL, CURLOPT_HEADER, 1);
	}

	function loadPage($URL, $isPost = false, $postData = array()) {
		$this->URL = $URL;
		$this->init_http_client();
		if (!$this->cURL || !$this->URL) {
			echo "Wrong method used to call me on page.";
			return false;
		}
		
		if($this->debugMode) {
			echo "Loading $URL<br />";
		}
		curl_setopt($this->cURL, CURLOPT_URL, $this->URL);
		if ($isPost) {
			if ( ! version_compare(phpversion(), '5.1.2', '>=') )
					$postData = _http_build_query($postData, null, '&');
			else
				$postData = http_build_query($postData, null, '&');
			curl_setopt($this->cURL, CURLOPT_POSTFIELDS, $postData);
		}
		
		if(curl_error($this->cURL)) {
			return false;
		}
		
		$this->rawResponse = curl_exec($this->cURL);
		if ( !empty($this->rawResponse) ) {
			$parts = explode("\r\n\r\n", $this->rawResponse);
			$headerLength = curl_getinfo($this->cURL, CURLINFO_HEADER_SIZE);
			$theHeaders = trim( substr($this->rawResponse, 0, $headerLength) );
			$this->setResponseBody(substr( $this->rawResponse, $headerLength ));
			if ( false !== strrpos($theHeaders, "\r\n\r\n") ) {
				$headerParts = explode("\r\n\r\n", $theHeaders);
				$theHeaders = $headerParts[ count($headerParts) -1 ];
			}
			$all_headers = WP_Http::processHeaders($theHeaders);
			$this->setResponseHeaders($all_headers['headers']);
			$this->setCookies($all_headers['cookies']);
		} 
		
		$this->setResponseCode(curl_getinfo($this->cURL, CURLINFO_HTTP_CODE ));
		curl_close($this->cURL);
		return true;
		
	}

	function loadPageA($URL, $isPost = false, $postData = array(), $headers = array()) {
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
			//echo $responseBody;
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

	function resetResponse() {
		$this->responseBody = "";
		$this->responseCode = "";
		$this->responseHeader = "";
		//$this->cookies = "";
	}

}

?>
