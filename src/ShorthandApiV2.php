<?php

namespace Drupal\shorthand;

use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Log\LoggerInterface;

/**
 * Class ShorthandApiV2.
 */
class ShorthandApiV2 implements ShorthandApiInterface {

  use StringTranslationTrait;

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
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ShorthandApi object.
   *
   * @param \GuzzleHttp\Client $http_client
   *   Http client service instance.
   * @param \Drupal\Core\Site\Settings $settings
   *   Settings service instance.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger interface.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   */
  public function __construct(Client $http_client, Settings $settings, FileSystemInterface $file_system, MessengerInterface $messenger, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->settings = $settings;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->logger = $logger;
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

    $stories = [];

    try {
      $response = $this->httpClient->get('v2/stories', [
        'base_uri' => $this->getBaseUri(),
        'headers' => $this->buildHeaders(),
      ]);

      $decoded = Json::decode((string) $response->getBody());

      if (isset($decoded)) {
        foreach ($decoded as $storydata) {
          $story = [
            'image' => $storydata['cover'],
            'id' => $storydata['id'],
            'metadata' => [
              'description' => $storydata['description'],
            ],
            'title' => $storydata['title'],
            'story_version' => '' . $storydata['version'],
          ];
          $stories[] = $story;
        }
      }

    }
    catch (BadResponseException $error) {
      $message = $error->getMessage();
      $this->messenger->addError($this->t('Server returned the following error: <em>@message</em>. Please check your settings or view log for more details.', ['@message' => $message]));
      $this->logger->error('<strong>' . $message . '</strong><br />' . $error->getTraceAsString());
      return FALSE;
    }

    return $stories;

  }

  /**
   * {@inheritdoc}
   */
  public function getStory($id) {

    try {
      $temp_path = $this->getStoryFileTempPath();
      $this->httpClient->get('v2/stories/' . $id, [
        'base_uri' => $this->getBaseUri(),
        'headers' => $this->buildHeaders(),
        'sink' => $temp_path,
        'timeout' => 120,
      ]);
    }
    catch (BadResponseException $error) {
      $message = $error->getMessage();
      $this->messenger->addError($message);
      $this->logger->error('<strong>' . $message . '</strong><br />' . $error->getTraceAsString());
    }

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
      'Authorization' => ' Token ' . $this->settings->get('shorthand_token'),
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
