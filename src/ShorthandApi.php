<?php

namespace Drupal\shorthand;

use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\Client;
use Drupal\Core\Site\Settings;

/**
 * Class ShorthandApi.
 *
 * @todo Service should implement a logger i.e. to log Exceptions messages.
 * @todo Catch exceptions when requests fail (host unreachable, timeout, etc.).
 */
class ShorthandApi implements ShorthandApiInterface {

  /**
   * Shorthand API URL.
   */
  const SHORTHAND_API_URL = 'https://app.shorthand.com/';

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
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new ShorthandApi object.
   *
   * @param \GuzzleHttp\Client $http_client
   *   Http client service instance.
   * @param \Drupal\Core\Site\Settings $settings
   *   Settings service instance.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(Client $http_client, Settings $settings, FileSystemInterface $file_system) {
    $this->httpClient = $http_client;
    $this->settings = $settings;
    $this->fileSystem = $file_system;
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
    $response = $this->httpClient->get('api/index', [
      'base_uri' => $this->getBaseUri(),
      'query' => $this->buildQuery(),
    ]);

    $decoded = Json::decode((string) $response->getBody());

    return isset($decoded['stories']) ? $decoded['stories'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getStory($id) {

    $temp_path = $this->getStoryFileTempPath();
    $this->httpClient->get('api/story/' . $id, [
      'base_uri' => $this->getBaseUri(),
      'query' => $this->buildQuery(),
      'sink' => $temp_path,
      'timeout' => 120,
    ]);

    return $temp_path;
  }

  /**
   * Build request queries, including authentication parameters.
   *
   * @return array
   *   URL Query parameters array.
   */
  protected function buildQuery() {
    return [
      'user' => $this->settings->get('shorthand_user_id'),
      'token' => $this->settings->get('shorthand_token'),
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
    return $this->fileSystem->getTempDirectory() . DIRECTORY_SEPARATOR . uniqid('shorthand-') . '.zip';
  }

}
