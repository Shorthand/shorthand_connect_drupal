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
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager, LoggerInterface $logger) {
    $this->configFactory = $config_factory;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->logger = $logger;
  }

  /**
   * Gets the configured stream wrapper scheme.
   *
   * @return string
   *   The stream wrapper scheme (e.g., 'public', 's3', 'azure').
   */
  public function getConfiguredScheme(): string {
    $config = $this->configFactory->get('shorthand.settings');
    $scheme = $config->get('file_stream_wrapper');
    
    // Default to 'public' if not configured.
    if (empty($scheme)) {
      $scheme = 'public';
    }
    
    // Validate that the configured stream wrapper is available.
    if (!$this->isStreamWrapperAvailable($scheme)) {
      $this->logger->warning('Configured stream wrapper @scheme is not available. Falling back to public://.', ['@scheme' => $scheme]);
      return 'public';
    }
    
    return $scheme;
  }

  /**
   * Builds a storage URI with the configured stream wrapper.
   *
   * @param string $path
   *   The path to append to the stream wrapper.
   *
   * @return string
   *   The full URI (e.g., 'public://shorthand/stories/...').
   */
  public function getStorageUri(string $path = ''): string {
    $scheme = $this->getConfiguredScheme();
    $uri = $scheme . '://';
    
    if (!empty($path)) {
      // Remove leading slash if present.
      $path = ltrim($path, '/');
      $uri .= $path;
    }
    
    return $uri;
  }

  /**
   * Checks if a stream wrapper is available.
   *
   * @param string $scheme
   *   The stream wrapper scheme to check.
   *
   * @return bool
   *   TRUE if the stream wrapper is available, FALSE otherwise.
   */
  public function isStreamWrapperAvailable(string $scheme): bool {
    $wrapper = $this->streamWrapperManager->getViaScheme($scheme);
    return (bool) $wrapper;
  }

  /**
   * Gets all available writable stream wrappers.
   *
   * @return array
   *   An array of available stream wrappers keyed by scheme.
   */
  public function getAvailableStreamWrappers(): array {
    $wrappers = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::WRITE_VISIBLE);
    $available = [];
    foreach ($wrappers as $scheme => $wrapper) {
      $available[$scheme] = $wrapper;
    }
    return $available;
  }

  /**
   * Migrates existing stories from one stream wrapper to another.
   *
   * @param string $from_scheme
   *   The source stream wrapper scheme.
   * @param string $to_scheme
   *   The destination stream wrapper scheme.
   *
   * @return bool
   *   TRUE if migration was successful, FALSE otherwise.
   */
  public function migrateStories(string $from_scheme, string $to_scheme): bool {
    // This method can be implemented if needed for migration between storage backends.
    // For now, it's a placeholder for future functionality.
    $this->logger->info('Story migration from @from to @to requested.', [
      '@from' => $from_scheme,
      '@to' => $to_scheme,
    ]);
    
    // Implementation would involve:
    // 1. List all files in the source stream wrapper
    // 2. Copy each file to the destination stream wrapper
    // 3. Update any database references if needed
    // 4. Optionally delete source files after successful migration
    
    return TRUE;
  }

}
