<?php

namespace Drupal\shorthand;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for handling stream wrapper operations for Shorthand module.
 */
class ShorthandStreamWrapper {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a ShorthandStreamWrapper object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager, LoggerInterface $logger) {
    $this->configFactory = $config_factory;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->logger = $logger;
  }

  /**
   * Gets the configured stream wrapper scheme.
   */
  public function getConfiguredScheme(): string {
    $config = $this->configFactory->get('shorthand.settings');
    $scheme = $config->get('file_stream_wrapper') ?: 'public';
    if (!$this->isStreamWrapperAvailable($scheme)) {
      $this->logger->warning('Configured stream wrapper @scheme is not available. Falling back to public://.', ['@scheme' => $scheme]);
      return 'public';
    }
    return $scheme;
  }

  /**
   * Builds a storage URI with the configured stream wrapper.
   */
  public function getStorageUri(string $path = ''): string {
    $scheme = $this->getConfiguredScheme();
    $uri = $scheme . '://';
    if (!empty($path)) {
      $path = ltrim($path, '/');
      $uri .= $path;
    }
    return $uri;
  }

  /**
   * Checks if a stream wrapper is available.
   */
  public function isStreamWrapperAvailable(string $scheme): bool {
    return (bool) $this->streamWrapperManager->getViaScheme($scheme);
  }

}

