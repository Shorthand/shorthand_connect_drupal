<?php

function sh_get_profile($user_id, $token) {
	
	$serverURL = 'http://localhost:8000';

	$valid_token = false;

	$data = array();

	//Attempt to connect to the server
	//USE drupal_http_request
	if($token && $user_id) {
		$url = $serverURL.'/api/profile/';
		$vars = 'user='.$user_id.'&token='.$token;
		$response = drupal_http_request($url, array(
			'method' => 'POST',
			'data'=>$vars,
			'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
		));
		$data = json_decode($response->data);
	}
	return $data;
}

// function sh_get_stories() {
// 	global $serverURL;
// 	$token = get_option('sh_token_key');
// 	$user_id = get_option('sh_user_id');

// 	$valid_token = false;

// 	$stories = array();

// 	//Attempt to connect to the server
// 	if($token && $user_id) {
// 		$url = $serverURL.'/api/index/';
// 		$vars = 'user='.$user_id.'&token='.$token;
// 		$ch = curl_init( $url );
// 		curl_setopt( $ch, CURLOPT_POST, 1);
// 		curl_setopt( $ch, CURLOPT_POSTFIELDS, $vars);
// 		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
// 		curl_setopt( $ch, CURLOPT_HEADER, 0);
// 		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
// 		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
// 		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
// 		$response = curl_exec( $ch );
// 		$data = json_decode($response);
// 		if(isset($data->stories)) {
// 			$valid_token = true;
// 			$stories = $data->stories;
// 		}
// 	}
// 	return $stories;
// }

// function sh_get_story_path($post_id, $story_id) {
// 	WP_Filesystem();
// 	$destination = wp_upload_dir();
// 	$destination_path = $destination['path'].'/shorthand/'.$post_id.'/'.$story_id;
// 	return $destination_path;
// }

// function sh_copy_story($post_id, $story_id) {

// 	WP_Filesystem();
// 	$destination = wp_upload_dir();
// 	$destination_path = $destination['path'].'/shorthand/'.$post_id.'/'.$story_id;

// 	global $serverURL;
// 	$token = get_option('sh_token_key');
// 	$user_id = get_option('sh_user_id');

// 	$valid_token = false;

// 	$story = array();

// 	//Attempt to connect to the server
// 	if($token && $user_id) {
// 		$url = $serverURL.'/api/story/'.$story_id.'/';
// 		$vars = 'user='.$user_id.'&token='.$token;
// 		$ch = curl_init( $url );
// 		curl_setopt( $ch, CURLOPT_POST, 1);
// 		curl_setopt( $ch, CURLOPT_POSTFIELDS, $vars);
// 		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
// 		curl_setopt( $ch, CURLOPT_HEADER, 0);
// 		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
// 		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
// 		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
// 		$response = curl_exec( $ch );
// 		$zipfile = tempnam('/tmp', 'sh_zip');
// 		$handle = fopen($zipfile, "w");
// 		fwrite($handle, $response);
// 		fclose($handle);

// 		$unzipfile = unzip_file( $zipfile, $destination_path);
   
//    		if ( $unzipfile ) {
//    			$story['path'] = $destination_path;
//    		}
// 	}
// 	return $story;
// }

?>