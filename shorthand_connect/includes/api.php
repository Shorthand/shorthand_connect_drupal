<?php

function sh_get_profile($user_id, $token) {
	
	$serverURL = variable_get('sh_server_url', '');

	$valid_token = false;

	$data = array();

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

function sh_get_stories() {

 	$serverURL = variable_get('sh_server_url', '');

 	$token = variable_get('shorthand_connect_token', '');
 	$user_id = variable_get('shorthand_connect_user_id', '');

 	$stories = array();

// 	//Attempt to connect to the server
 	if($token && $user_id) {
 		$url = $serverURL.'/api/index/';
 		$vars = 'user='.$user_id.'&token='.$token;
 		$response = drupal_http_request($url, array(
			'method' => 'POST',
			'data'=>$vars,
			'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
		));
		if($response->data) {
	 		$data = json_decode($response->data);
 			if(isset($data->stories)) {
 				$valid_token = true;
 				$stories = $data->stories;
 			}
 		}
 	}
 	return $stories;
}

function sh_copy_story($post_id, $story_id) {

	$destination = drupal_realpath('public://');
	$destination_path = $destination.'/shorthand/'.$post_id.'/'.$story_id;
	$destination_url = file_create_url('public://').'/shorthand/'.$post_id.'/'.$story_id;

	$serverURL = variable_get('sh_server_url', '');
	$token = variable_get('shorthand_connect_token', '');
 	$user_id = variable_get('shorthand_connect_user_id', '');

	$story = array();

	//Attempt to connect to the server
	if($token && $user_id) {
  		$url = $serverURL.'/api/story/'.$story_id.'/';
 		$vars = 'user='.$user_id.'&token='.$token;
 		$response = drupal_http_request($url, array(
			'method' => 'POST',
			'data'=>$vars,
			'headers' => array('Content-Type' => 'application/x-www-form-urlencoded','Cache-Control' => 'no-cache','Pragma' => 'no-cache','Connection' => 'keep-alive'),
			'timeout' => 60.0	
		));
		$zipfile = tempnam('/tmp', 'sh_zip');
		$handle = fopen($zipfile, "w");
 		fwrite($handle, $response->data);
 		fclose($handle);
 		$zip = new ZipArchive;
 		if ($zip->open($zipfile) === TRUE) {
		    $zip->extractTo($destination_path);
    		$zip->close();
    		$story['path'] = $destination_path;
    		$story['url'] = $destination_url;
		} else {
    		echo 'Could not copy story';
    		die();
		}
	}
	return $story;
}

// function sh_get_story_path($post_id, $story_id) {
// 	WP_Filesystem();
// 	$destination = wp_upload_dir();
// 	$destination_path = $destination['path'].'/shorthand/'.$post_id.'/'.$story_id;
// 	return $destination_path;
// }

?>