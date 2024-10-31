<?php

/**
* Misc class with different functions
* 
* @author - Keith Dsouza (http://keithdsouza.com)
**/


class NUSMisc {
	function random() {
		$chars = "abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ023456789";
		srand((double) microtime() * 1000000);
		$i = 0;
		$rand = '';

		while ($i <= 7) {
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$rand = $rand . $tmp;
			$i++;
		}
		return $rand;
	}

	function print_array($array) {
		echo "<pre>";
		print_r($array);
		echo "</pre>";
	}
	
	function debug_dump($filename, $filedata) {
		$handle = fopen("C:/test/".$filename, 'w');
		fwrite($handle, $filedata);
		fclose($handle);
	}
	
	function print_error($message) {
		echo "<h3 style='color:red'>Error Details</h3>";
		echo "OOPS!!! Something went wrong while fetching the unconfirmed subscribers. Use the error code given below, if you want to submit a support request.<br /><br />";
		echo "<strong>Error Code: </strong>".$message."<br /><br />";
	}
}
?>