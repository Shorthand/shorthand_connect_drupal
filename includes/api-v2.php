<?php

/**
 * @file
 * Functions to interact with Shorthand API.
 */

/**
 * Returns an object of the users profile.
 *
 * @param int $user_id
 *   The user id of the Shorthand account.
 * @param string $token
 *   The Shorthand API token.
 *
 * @return array|mixed
 *   Data from SHorthand API.
 */
function sh_get_profile($user_id, $token, $version = NULL) {
  $data = [];
  if ($version == 'v1') {
    $serverURL = variable_get('shorthand_server_url', 'https://app.shorthand.com');
    if ($token && $user_id) {
      $url = $serverURL . '/api/profile/';
      $vars = 'user=' . $user_id . '&token=' . $token;
      $response = drupal_http_request($url, [
        'method' => 'POST',
        'data' => $vars,
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
      ]);
      $data = json_decode($response->data);
    }
  }
  else {
    $serverURL = variable_get('shorthand_server_v2_url', 'https://api.shorthand.com');
    if ($token) {
      $url = $serverURL . '/v2/token-info';
      $response = drupal_http_request($url, [
        'method' => 'GET',
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded', 'Authorization' => 'Token ' . $token],
      ]);
      $data = json_decode($response->data);
      $data->username = $data->name . ' (' . $data->token_type . ' Token)';
    }
  }
  return $data;
}

/**
 * Returns a list of stories that exist for the current user.
 *
 * @return array
 *   Stories from Shorthand.
 */
function sh_get_stories() {

  $serverURL = variable_get('shorthand_server_v2_url', 'https://api.shorthand.com');

  $token = _shorthand_get_token();

  $stories = [];

  // Attempt to connect to the server.
  if ($token) {
    $url = $serverURL . '/v2/stories/';
    $response = drupal_http_request($url, [
      'method' => 'GET',
      'headers' => ['Content-Type' => 'application/x-www-form-urlencoded', 'Authorization' => 'Token ' . $token],
    ]);
    if (isset($response->data)) {
      $data = json_decode($response->data);
      if (isset($data)) {
        $stories = [];

        if (isset($data->status)) {
          return NULL;
        }
        foreach ($data as $storydata) {
          $description = '';
          if (isset($storydata->description)) {
            $description = $storydata->description;
          }
          $story = [
            'image' => $storydata->cover,
            'id' => $storydata->id,
            'metadata' => (object) [
              'description' => $description,
            ],
            'title' => $storydata->title,
          ];
          $stories[] = (object) $story;
        }
      }
    }
    else {
      drupal_set_message(t('Could not connect to Shorthand, please check your Shorthand module settings.'), 'error');
    }
  }
  return $stories;
}

/**
 * Returns a ZIP archive of the story.
 *
 * @param string $node_id
 *   The node id.
 * @param string $story_id
 *   The story id.
 *
 * @return array
 *   Array of story data.
 */
function sh_copy_story($node_id, $story_id) {
  $destination = drupal_realpath('public://');
  $destination_path = $destination . '/shorthand/' . $node_id . '/' . $story_id;
  $destination_url = file_create_url('public://') . 'shorthand/' . $node_id . '/' . $story_id;

  $serverURL = variable_get('shorthand_server_v2_url', 'https://api.shorthand.com');
  $token = variable_get('shorthand_token', '');
  // $user_id = variable_get('shorthand_user_id', '');.
  $story = [];

  // Attempt to connect to the server.
  if ($token) {
    $url = $serverURL . '/v2/stories/' . $story_id;
    $ch = curl_init($url);

    $zipfile = tempnam('/tmp', 'sh_zip');
    $ziphandle = fopen($zipfile, "w");
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Token ' . $token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, variable_get('shorthand_curlopt_ssl_verifypeer', 1));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FILE, $ziphandle);
    $response = curl_exec($ch);

    if ($response == 1) {
      try {
        shorthand_archive_extract($zipfile, $destination_path, TRUE);
        $story['path'] = $destination_path;
        $story['url'] = $destination_url;
      }
      catch (Exception $e) {
        // log.
        $story['error'] = [
          'pretty' => 'Could not add story',
          'error' => $e,
          'response' => $response,
        ];
      }

    }
    else {
      // log.
      $story['error'] = [
        'pretty' => 'Could not upload file',
        'error' => curl_error($ch),
        'response' => $response,
      ];
    }
  }

  return $story;
}
