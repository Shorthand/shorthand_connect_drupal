<?php

namespace Drupal\shorthand;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Log\LoggerInterface;

/**
 * Class for Shorthand's API handling (Versioning to be deprecated).
 *
 * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
 *   ShorthandApi class.
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
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

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
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger interface.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   * @param Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory instance.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi class.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::__construct()
   */
  public function __construct(Client $http_client, FileSystemInterface $file_system, MessengerInterface $messenger, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory;
    $this->httpClient = $http_client;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi:getProfile().
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::getProfile()
   */
  public function getProfile() {
    // @todo Implement getProfile() method.
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi:getPublishingConfigurations().
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::getPublishingConfigurations()
   */
  public function getPublishingConfigurations() {

    $configs = [];

    try {
      $response = $this->httpClient->get('v2/publish-configurations', [
        'base_uri' => $this->getBaseUri(),
        'headers' => $this->buildHeaders(),
      ]);

      $decoded = Json::decode((string) $response->getBody());

      if (isset($decoded)) {
        foreach ($decoded as $configdata) {
          $config = [
            'name' => $configdata['name'],
            'id' => $configdata['id'],
            'description' => $configdata['description'],
            'baseUrl' => $configdata['baseUrl'],
          ];
          $configs[] = $config;
        }
      }

    }
    catch (BadResponseException $error) {
      $message = $error->getMessage();
      $this->messenger->addError($this->t('Server returned the following error: <em>@message</em>. Please check your settings or view log for more details.', ['@message' => $message]));
      $this->logger->error('<strong>' . $message . '</strong><br />' . $error->getTraceAsString());
      return FALSE;
    }

    return $configs;

  }

  /**
   * Return Shorthand API base uri.
   *
   * @return string
   *   Shorthand API base url.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi:getBaseUri().
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::getBaseUri()
   */
  protected function getBaseUri() {
    return self::SHORTHAND_API_URL;
  }

  /**
   * Build request headers, including authentication parameters.
   *
   * @return array
   *   Headers parameters array.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi:buildHeaders().
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::buildHeaders()
   */
  protected function buildHeaders($token = NULL) {
    $config = $this->config->get('shorthand.settings');
    $config_token = $config->get('shorthand_token');
    return [
      'Authorization' => ' Token ' . ($token ?? $config_token),
      'Content-Type' => 'application/json; charset=utf-8',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi:getStories().
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::getStories()
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
            'image' => $storydata['signedCover'],
            'id' => $storydata['id'],
            'metadata' => [
              'description' => $storydata['description'],
              'authors' => '' . $storydata['authors'],
              'keywords' => '' . $storydata['keywords'],
            ],
            'title' => $storydata['title'],
            'external_url' => '' . $storydata['url'],
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
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi:getStory().
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::getStory()
   */
  public function getStory($id, $params) {

    try {
      $temp_path = $this->getStoryFileTempPath();
      $this->httpClient->get('v2/stories/' . $id . (isset($params) ? '?' . http_build_query($params) : ''), [
        'base_uri' => $this->getBaseUri(),
        'headers' => $this->buildHeaders(),
        'sink' => $temp_path,
        'timeout' => $this->config->get('shorthand.settings')
          ->get('request_timeout'),
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
   * Return path to temporary file where to upload story .zip file.
   *
   * @return string
   *   Path.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi:getStoryFileTempPath().
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::getStoryFileTempPath()
   */
  protected function getStoryFileTempPath() {
    return $this->fileSystem->getTempDirectory() . DIRECTORY_SEPARATOR . uniqid('shorthand-') . '.zip';
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi:publishAssets().
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::publishAssets()
   */
  public function publishAssets($id, $config) {
    $request = NULL;
    try {
      $request = $this->httpClient->post('v2/stories/' . $id . '/publish', [
        'base_uri' => $this->getBaseUri(),
        'headers' => $this->buildHeaders(),
        'body' => json_encode($this->buildBody($config->id)),
        'timeout' => $this->config->get('shorthand.settings')
          ->get('request_timeout'),
      ]);
    }
    catch (BadResponseException $error) {
      $message = $error->getMessage();
      $this->messenger->addError($message);
      $this->messenger->addError($request);
      $this->logger->error('<strong>' . $message . '</strong><br />' . $error->getTraceAsString());
    }
  }

  /**
   * Build request body for external publishing.
   *
   * @return array
   *   Body array.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi:buildBody().
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::buildBody()
   */
  protected function buildBody($config) {
    return [
      'config' => $config,
      'url' => '',
      "publishSubset" => "assets_only",
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use
   *   ShorthandApi:validateApiKey().
   *
   * @see https://www.drupal.org/project/shorthand/issues/3250535
   * @see Drupal\shorthand\ShorthandApiInterface::validateApiKey()
   */
  public function validateApiKey($token) {
    try {
      error_log('TESTING THIS');
      error_log($this->getBaseUri());
      $this->httpClient->get('v2/token-info/', [
        'base_uri' => $this->getBaseUri(),
        'headers' => $this->buildHeaders($token),
        'timeout' => $this->config->get('shorthand.settings')
          ->get('request_timeout'),
      ]);
    }
    catch (BadResponseException $error) {
      $message = $error->getMessage();
      $this->messenger->addError($message);
      $this->logger->error('<strong>' . $message . '</strong><br />' . $error->getTraceAsString());
      return FALSE;
    }

    return TRUE;
  }

}
