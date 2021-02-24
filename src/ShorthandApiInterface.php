<?php

namespace Drupal\shorthand;

/**
 * Interface for Shorthand API.
 */
interface ShorthandApiInterface {

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
   * @return array
   *   Stories from Shorthand.
   */
  public function getStories();

  /**
   * Download the story files and return the .zip file URI.
   *
   * @param string $id
   *   Story ID.
   *
   * @return string
   *   Drupal URI to the story .zip file.
   */
  public function getStory($id);

  /**
   * Validate API key.
   *
   * @return bool
   *   TRUE if API key is valid.
   */
  public function validateApiKey($token);

}
