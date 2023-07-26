<?php

namespace Drupal\shorthand;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;

/**
 * Class for Shorthand's API handling.
 */
class ShorthandApi implements ShorthandApiInterface {

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
   */
  public function validateApiKey($token) {
    try {
      $this->httpClient->get('v2/token-info/', [
        'base_uri' => $this->getBaseUri(),
        'headers' => $this->buildHeaders($token),
        'timeout' => $this->config->get('shorthand.settings')
          ->get('request_timeout'),
      ]);
    }
    catch (\Exception $error) {
      $message = $this->t('<strong>Error validating API key</strong>. Details: <pre>@error</pre>', [
        '@error' => $error->getMessage(),
      ]);
      $this->messenger->addError($message);
      $this->logger->error($message);

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Return Shorthand API base uri.
   *
   * @return string
   *   Shorthand API base url.
   */
  protected function getBaseUri() {
    return self::SHORTHAND_API_URL;
  }

  /**
   * Build request headers, including authentication parameters.
   *
   * @param string $token
   *   Header token.
   *
   * @return array
   *   Header parameter array.
   *
   * @throws \Exception
   */
  protected function buildHeaders($token = NULL) {
    $config = $this->config->getEditable('shorthand.settings');
    $config_token = $config->get('shorthand_token') ?? NULL;
    if (!$token && !$config_token) {
      throw new \Exception('A valid Shorthand token is required');
    }

    return [
      'Authorization' => ' Token ' . ($token ?? $config_token),
      'Content-Type' => 'application/json; charset=utf-8',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile() {
    // @todo Implement getProfile() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getPublishingConfigurations() {

    $configurations = [];

    try {
      $response = $this->httpClient->get('v2/publish-configurations', [
        'base_uri' => $this->getBaseUri(),
        'headers' => $this->buildHeaders(),
      ]);

      $decoded = Json::decode((string) $response->getBody());

      if (isset($decoded)) {
        foreach ($decoded as $configData) {
          $config = [
            'name' => $configData['name'],
            'id' => $configData['id'],
            'description' => $configData['description'],
            'baseUrl' => $configData['baseUrl'],
          ];
          $configurations[] = $config;
        }
      }

    }
    catch (BadResponseException | \Exception $error) {
      $message = $error->getMessage();
      $this->messenger->addError($this->t('Server returned the following error: <em>@message</em>. Please check your settings or view log for more details.', [
        '@message' => $message,
      ]));
      $this->logger->error('<strong>' . $message . '</strong><br />' . $error->getTraceAsString());
      return FALSE;
    }

    return $configurations;

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
            'image' => $storydata['signedCover'],
            'id' => $storydata['id'],
            'metadata' => [
              'description' => $storydata['description'],
              'authors' => '' . $storydata['authors'],
              'keywords' => '' . $storydata['keywords'],
            ],
            'title' => $storydata['title'],
            'status' => $storydata['status'],
            'published' => $storydata['lastPublishedAt'],
            'updated' => $storydata['updatedAt'],
            'external_url' => '' . $storydata['url'],
            'api_version' => '' . $storydata['version'],
          ];
          $stories[] = $story;
        }
      }
    }
    catch (BadResponseException | ConnectException | \Exception $error) {
      $message = $error->getMessage();
      $this->messenger->addError($this->t('Server returned the following error: <em>@message</em>. Please check your settings or view log for more details.', [
        '@message' => $message,
      ]));
      $this->logger->error('<strong>' . $message . '</strong><br />' . $error->getTraceAsString());
      return FALSE;
    }

    return $stories;

  }

  /**
   * {@inheritdoc}
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
    catch (BadResponseException | \Exception $error) {
      $message = $error->getMessage();
      $this->messenger->addError($this->t('Server returned the following error: <em>@message</em>. Please check your settings or view log for more details.', [
        '@message' => $message,
      ]));
      $this->logger->error('<strong>' . $message . '</strong><br />' . $error->getTraceAsString());
    }

    return $temp_path;
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

  /**
   * {@inheritdoc}
   */
  public function publishAssets($id, $config) {

    try {
      $this->httpClient->post('v2/stories/' . $id . '/publish', [
        'base_uri' => $this->getBaseUri(),
        'headers' => $this->buildHeaders(),
        'body' => json_encode($this->buildBody($config->id)),
        'timeout' => $this->config->get('shorthand.settings')->get('request_timeout'),
      ]);
    }
    catch (BadResponseException | \Exception $error) {
      $message = $error->getMessage();
      $this->messenger->addError($this->t('Server returned the following error: <em>@message</em>. Please check your settings or view log for more details.', [
        '@message' => $message,
      ]));
      $this->logger->error('<strong>' . $message . '</strong><br />' . $error->getTraceAsString());
      return FALSE;
    }

  }

  /**
   * Build request body for external publishing.
   *
   * @return array
   *   Response body as array.
   */
  protected function buildBody($config) {
    return [
      'config' => $config,
      'url' => '',
      "publishSubset" => "assets_only",
    ];
  }

}
