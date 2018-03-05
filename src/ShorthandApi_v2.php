<?php

namespace Drupal\shorthand;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use Drupal\Core\Site\Settings;

/**
 * Class ShorthandApi.
 *
 * @todo Service should implement a logger i.e. to log Exceptions messages.
 * @todo Catch exceptions when requests fail (host unreachable, timeout, etc.).
 */
class ShorthandApi_v2 implements ShorthandApiInterface {

  /**
   * Shorthand API URL.
   */
  const SHORTHAND_API_URL = 'https://api.shorthand.com/';

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;
  /**
   * Drupal\Core\Site\Settings definition.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a new ShorthandApi object.
   *
   * @param \GuzzleHttp\Client $http_client
   *   Http client service instance.
   * @param \Drupal\Core\Site\Settings $settings
   *   Settings service instance.
   */
  public function __construct(Client $http_client, Settings $settings) {
    $this->httpClient = $http_client;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile() {
    // TODO: Implement getProfile() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getStories() {
    $response = $this->httpClient->get('v2/stories', [
      'base_uri' => $this->getBaseUri(),
      'headers' => $this->buildHeaders(),
    ]);

    $decoded = Json::decode((string) $response->getBody());

    $stories = array();

    if(isset($decoded)) {
      foreach($decoded as $storydata) {
        $story = array(
          'image' => $storydata['cover'],
          'id' => $storydata['id'],
          'metadata' => array(
            'description' => $storydata['description']
          ),
          'title' => $storydata['title'],
          'story_version' => ''.$storydata['version']
        );
        $stories[] = $story;
      }
    }

    return $stories;
  }

  /**
   * {@inheritdoc}
   */
  public function getStory($id) {

    $temp_path = $this->getStoryFileTempPath();
    $this->httpClient->get('v2/stories/' . $id, [
      'base_uri' => $this->getBaseUri(),
      'headers' => $this->buildHeaders(),
      'sink' => $temp_path,
      'timeout' => 120,
    ]);

    return $temp_path;
  }

  /**
   * Build request headers, including authentication parameters.
   *
   * @return array
   *   Headers parameters array.
   */
  protected function buildHeaders() {
    return [
      'Authorization' => ' Token '.$this->settings->get('shorthand_token'),
    ];
  }

  /**
   * Return Shorthand API base uri.
   *
   * @return string
   *   Shorthand API base url.
   */
  protected function getBaseUri() {
    return $this->settings->get('shorthand_server_url', self::SHORTHAND_API_URL);
  }

  /**
   * Return path to temporary file where to upload story .zip file.
   *
   * @return string
   *   Path.
   */
  protected function getStoryFileTempPath() {
    return file_directory_temp() . DIRECTORY_SEPARATOR . uniqid('shorthand-') . '.zip';
  }

}
