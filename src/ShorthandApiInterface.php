<?php

namespace Drupal\shorthand;

/**
 * Interface for Shorthand API.
 */
interface ShorthandApiInterface {

  /**
   * Shorthand API URL.
   */
  const SHORTHAND_API_URL = 'https://api.shorthand.com/';

  /**
   * Whether API requests should verify SSL certificates.
   */
  const SHORTHAND_API_VERIFY_SSL = TRUE;

  /**
   * Get profile data.
   *
   * @return array
   *   JSON decode profile data from Shorthand API.
   */
  public function getProfile();

  /**
   * Get stories.
   *
   * @param array $params
   *   Optional query parameters to pass to the Shorthand API.
   *
   * @return array|bool
   *   Stories from Shorthand or FALSE if not able to retrieve.
   */
  public function getStories(array $params = []);

  /**
   * Download the story files and return the .zip file URI.
   *
   * @param string $id
   *   Story ID.
   * @param object $params
   *   Params for GET request.
   *
   * @return string
   *   Drupal URI to the story .zip file.
   */
  public function getStory($id, $params);

  /**
   * Download the story files and return the .zip file URI.
   *
   * @param string $id
   *   Story ID.
   * @param object $config
   *   Publish configuration object.
   */
  public function publishAssets($id, $config);

  /**
   * Validate API key.
   *
   * @return bool
   *   TRUE if API key is valid.
   */
  public function validateApiKey($token);

}
