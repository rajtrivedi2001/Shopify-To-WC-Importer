<?php
/**
 * Returns Current Time As Micro Timer
 * @return number
 */
function timer(){
	$time = microtime();
	$time = explode(' ', $time);
	return $time[1] + $time[0];
}


/**
 * Output Buffer Instant Update To Browser
 * @param string $text
 * @param boolean $br
 */
function text($text,$br = true){
	global $messageLogs;
	
	$message = '';
	if($br){ $message = $text.'<br/>'; $messageLogs[] = $message;  }
	else {$message = $text; $messageLogs[] = $message;	}
	if(instant_update){ echo $message; echo str_pad('',4096)."\n";   ob_flush(); flush(); }	
}

/**
 * Base Setup
 */
function base_setup(){
	if(instant_update){
		if (ob_get_level() == 0) ob_start();
	}
	set_time_limit(0);
}

/**
 * Base Setup
 */
function base_footer_setup(){
	if(instant_update){
		ob_end_flush();
	}
}

/**
 * Runs CRUL Request USING PHP
 * @param string $url
 * @param post details $fields
 */
function request($url){
	$request_url = 'https://'.api_key.':'.api_pass.'@'.get_url().'/'.$url;
	$session = curl_init();
	curl_setopt($session, CURLOPT_URL, $request_url);
	curl_setopt($session, CURLOPT_HTTPGET, 1);
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content -Type: application/json'));
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	if(ereg("^(https)",$request_url)) curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
	$response = curl_exec($session);
	curl_close($session);
	return json_decode($response,'true');
}

/**
 * Generates Clean URL For CURL Request
 * @return multitype:string NULL
 */
function gen_url(){
	$fields_string = array();
	$fields_string['count'] = count($fields);
	$fields_string['count'] = '';
	foreach($fields as $key=>$value) { 
		$fields_string['count'] .= $key.'='.$value.'&';
	}
	rtrim($fields_string['count'], '&');
	return $fields_string;
}

/**
 * Generates Clean API URL
 * @return unknown
 */
function get_url() {
	$input = trim(site_url, '/');
	if (!preg_match('#^http(s)?://#', $input)) { $input = 'http://' . $input;}
	$urlParts = parse_url($input);
	$domain = preg_replace('/^www\./', '', $urlParts['host']);
	return $domain;
}

function save_file($name,$data){
	file_put_contents(dataFOLDER.$name,$data);
}
 

function saveLOG(){
	global $messageLogs;
	if(defined('log_type') && log_type == 'json_generator'){
		save_file('json_fetch_log.html',implode(" ",$messageLogs));
		return true;
	}
	if(defined('log_type') && log_type == 'product_import'){
		save_file('product_import_log.html',implode(" ",$messageLogs));
		return true;
	}
	if(!defined('log_type')){
		save_file(date('h_i_s.html'),implode(" ",$messageLogs));
		return true;
		
	} 
}
?>
 
