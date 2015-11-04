<?php
/*#############################################################
        === General Library © James Mickley 2014 ===
This file contains a general library of all-purpose functions


##### Public Function Reference #####
* tab(num, [line]) 				This function adds tabs and optional newlines for pretty-printing HTML
* left(num, str)				Returns the leftmost num characters from str
* right(num, str)				Returns the rightmost num characters from str
* mid(start, stop, str)			Returns the characters between start and stop in str
* check_selected(va, arr)		Checks which choices in a select box or dropdown box have been selected.				
* dmm2ddd(str)					Converts degrees and decimal minutes to decimal degrees
* ddd2dmm(str)					Converts degrees, minutes, and seconds to decimal degrees


##### Version History #####
- 1/14/2014 JGM - Version 1.0:
        - Start of version numbering


#############################################################*/


// Function for adding tabs and newlines
// Use to pretty-print HTML
// If the optional line parameter is specified, the function adds a newline to the end of the line
function tab($num, $line=''){
	
	if($line == ''){
		return str_repeat('    ',$num);
	}else{
		return str_repeat('    ',$num).$line."\n";
	}
}

// VBA style left(), right(), and mid() functions for string manipulation
function left($num,$str) {
	return substr($str,0,$num);
}

function right($num,$str) {
	return substr($str,strlen($str)-$num,$num);
}

function mid($start,$stop,$str) {
	return substr($str,$start,$stop-$start+1);
}


// Function that checks which choices in a select box or dropdown box have been selected.
function check_selected($va, $arr) {
	for($i = 0; $i <= count($arr)-1; $i++){
		if(strpos("pre".$va, $arr[$i])){
			$select[$i] = " selected";
		}else{
			$select[$i] = "";
		}
	}
	return $select;
}

// Function to convert degrees and decimal minutes to decimal degrees
function dmm2ddd($str){
	$arr = explode(";",$str);
	for($i=0; $i< count($arr); $i++){
		$raw = trim($arr[$i]);
		$deg = substr($raw, 0, strpos($raw," "));
		$min = substr($raw, strpos($raw," ")+1);
		if($deg > 0){
			$arr[$i] = round($deg + $min/60, 4);
		}else{
			$arr[$i] = round($deg - $min/60, 4);
		}
	}
	return(implode(";", $arr));
}

// Function to convert degrees, minutes, and seconds to decimal degrees
function dms2ddd($str){
	$arr = explode(";",$str);
	for($i=0; $i< count($arr); $i++){
		$raw = trim($arr[$i]);
		$deg = substr($raw, 0, strpos($raw," "));
		$remain= substr($raw, strpos($raw," ")+1);
		$min = substr($remain, 0, strpos($remain," "));
		$sec= substr($remain, strpos($remain," ")+1);
		
		if($deg > 0){
			$arr[$i] = round($deg + $min/60 + $sec/3600, 4);
		}else{
			$arr[$i] = round($deg - ($min/60 + $sec/3600), 4);
		}
	}
	return(implode(";", $arr));
}

?>
