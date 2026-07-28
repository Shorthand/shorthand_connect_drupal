<?php

namespace Drupal\shorthand;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\shorthand\Controller\RemoteCollectionController;

/**
 * Maintains a cached index of locally downloaded Shorthand stories.
 *
 * Scanning the download folder and parsing every head.html is expensive at
 * large story counts, so the result is built lazily and cached until new
 * stories are downloaded.
 */
class LocalStoryIndex {

  /**
   * Cache ID holding the built index.
   */
  const CACHE_ID = 'shorthand.local_story_index';

  /**
   * Cache tag invalidated when local stories change.
   */
  const CACHE_TAG = 'shorthand:local_stories';

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Statically cached index for the current request.
   *
   * @var array|null
   */
  protected $stories = NULL;

  /**
   * Constructs a new LocalStoryIndex object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   */
  public function __construct(FileSystemInterface $file_system, CacheBackendInterface $cache, FileUrlGeneratorInterface $file_url_generator) {
    $this->fileSystem = $file_system;
    $this->cache = $cache;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Return all locally downloaded stories, keyed by story ID.
   *
   * @return array
   *   Story entries with keys id, title, description, image and versions
   *   (newest first).
   */
  public function getAll() {
    if ($this->stories !== NULL) {
      return $this->stories;
    }

    if ($cached = $this->cache->get(self::CACHE_ID)) {
      $this->stories = $cached->data;
      return $this->stories;
    }

    $this->stories = $this->scan();
    $this->cache->set(self::CACHE_ID, $this->stories, CacheBackendInterface::CACHE_PERMANENT, [self::CACHE_TAG]);

    return $this->stories;
  }

  /**
   * Return a single indexed story.
   *
   * @param string $story_id
   *   The Shorthand story ID.
   *
   * @return array|null
   *   The story entry, or NULL when not downloaded.
   */
  public function get($story_id) {
    return $this->getAll()[$story_id] ?? NULL;
  }

  /**
   * Return stories matching a keyword.
   *
   * @param string $keyword
   *   Keyword matched against story ID, title and description.
   * @param int $limit
   *   Maximum number of stories to return.
   *
   * @return array
   *   Matching story entries.
   */
  public function search($keyword, $limit = 20) {
    $keyword = mb_strtolower(trim((string) $keyword));
    $matches = [];

    foreach ($this->getAll() as $story) {
      $haystack = mb_strtolower($story['id'] . ' ' . $story['title'] . ' ' . $story['description']);
      if ($keyword === '' || str_contains($haystack, $keyword)) {
        $matches[] = $story;
        if (count($matches) >= $limit) {
          break;
        }
      }
    }

    return $matches;
  }

  /**
   * Return the most recently downloaded/updated stories.
   *
   * Recency is determined by each story's newest downloaded version, which
   * is named after the story's updated timestamp.
   *
   * @param int $limit
   *   Maximum number of stories to return.
   *
   * @return array
   *   Story entries, most recent first.
   */
  public function recent($limit = 5) {
    $stories = $this->getAll();
    uasort($stories, function (array $a, array $b) {
      return strcmp($b['versions'][0], $a['versions'][0]);
    });
    return array_slice($stories, 0, $limit);
  }

  /**
   * Discard the cached index so it is rebuilt on next access.
   */
  public function invalidate() {
    $this->stories = NULL;
    $this->cache->delete(self::CACHE_ID);
    Cache::invalidateTags([self::CACHE_TAG]);
  }

  /**
   * Build the index by scanning the local stories folder.
   *
   * @return array
   *   Story entries keyed by story ID, sorted by title.
   */
  protected function scan() {
    $stories = [];
    $destination_uri = 'public://' . RemoteCollectionController::SHORTHAND_STORY_BASE_PATH;

    if (!$this->fileSystem->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      return $stories;
    }

    $storyFolders = $this->fileSystem->scanDirectory($destination_uri, '/.*/', [
      'recurse' => FALSE,
      'key' => 'filename',
    ]);

    foreach (array_keys($storyFolders) as $story_id) {
      $storyVersionFolders = $this->fileSystem->scanDirectory($destination_uri . '/' . $story_id, '/.*/', [
        'recurse' => FALSE,
        'key' => 'filename',
      ]);

      $versions = array_keys($storyVersionFolders);
      if (empty($versions)) {
        continue;
      }
      rsort($versions);

      $story_version_uri = $destination_uri . '/' . $story_id . '/' . $versions[0];
      $metadata = $this->extractHeadMetadata($story_version_uri . '/head.html');

      $stories[$story_id] = [
        'id' => $story_id,
        'title' => $metadata['title'] !== '' ? $metadata['title'] : $story_id,
        'description' => $metadata['description'],
        'image' => $this->resolveImageUrl($metadata['image'], $story_version_uri),
        'versions' => $versions,
      ];
    }

    uasort($stories, function (array $a, array $b) {
      return strnatcasecmp($a['title'], $b['title']);
    });

    return $stories;
  }

  /**
   * Extract story metadata from a downloaded head.html file.
   *
   * @param string $head_uri
   *   The local head.html URI.
   *
   * @return array
   *   Extracted metadata with keys title, description and image.
   */
  public function extractHeadMetadata($head_uri) {
    $metadata = [
      'title' => '',
      'description' => '',
      'image' => '',
    ];

    if (!file_exists($head_uri)) {
      return $metadata;
    }

    $head = file_get_contents($head_uri);
    $meta = [];
    if (preg_match_all('/<meta\s+[^>]*>/i', $head, $tags)) {
      foreach ($tags[0] as $tag) {
        $attributes = $this->parseHtmlAttributes($tag);
        $name = strtolower($attributes['property'] ?? $attributes['name'] ?? '');
        if ($name !== '' && isset($attributes['content'])) {
          $meta[$name] = $attributes['content'];
        }
      }
    }

    $metadata['title'] = $meta['og:title'] ?? $meta['twitter:title'] ?? '';
    $metadata['description'] = $meta['og:description'] ?? $meta['description'] ?? $meta['twitter:description'] ?? '';
    $metadata['image'] = $meta['og:image'] ?? $meta['twitter:image'] ?? '';

    if ($metadata['title'] === '' && preg_match('/<title[^>]*>(.*?)<\/title>/is', $head, $matches)) {
      $metadata['title'] = html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5);
    }

    return $metadata;
  }

  /**
   * Resolve a metadata image URL to a local downloaded file URI.
   *
   * @param string $image
   *   The image URL extracted from metadata.
   * @param string $story_version_uri
   *   The local story version URI.
   *
   * @return string
   *   The local file URI, or an empty string when not found locally.
   */
  public function resolveLocalImageUri($image, $story_version_uri) {
    if ($image === '') {
      return '';
    }

    $relative_path = '';
    if (preg_match('/^https?:\/\//i', $image)) {
      $path = parse_url($image, PHP_URL_PATH) ?: '';
      $assets_position = strpos($path, '/assets/');
      if ($assets_position !== FALSE) {
        $relative_path = substr($path, $assets_position + 1);
      }
    }
    else {
      $relative_path = preg_replace('#^\./#', '', $image);
    }

    if ($relative_path === '') {
      return '';
    }

    $relative_path = rawurldecode($relative_path);
    if (str_contains($relative_path, '..') || preg_match('#^[\\\\/]#', $relative_path) || preg_match('#[\\\\]#', $relative_path)) {
      return '';
    }

    $local_uri = $story_version_uri . '/' . $relative_path;
    return file_exists($local_uri) ? $local_uri : '';
  }

  /**
   * Resolve a metadata image URL for display, preferring the local copy.
   *
   * @param string $image
   *   The image URL extracted from metadata.
   * @param string $story_version_uri
   *   The local story version URI.
   *
   * @return string
   *   A URL suitable for an image src.
   */
  protected function resolveImageUrl($image, $story_version_uri) {
    $local_uri = $this->resolveLocalImageUri($image, $story_version_uri);
    if ($local_uri !== '') {
      return $this->fileUrlGenerator->generateString($local_uri);
    }

    return $image;
  }

  /**
   * Parse simple HTML tag attributes.
   *
   * @param string $tag
   *   The HTML tag.
   *
   * @return array
   *   Attribute values keyed by lowercase attribute name.
   */
  protected function parseHtmlAttributes($tag) {
    $attributes = [];
    if (preg_match_all('/([a-zA-Z_:.-]+)\s*=\s*(["\'])(.*?)\2/s', $tag, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $attributes[strtolower($match[1])] = html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5);
      }
    }
    return $attributes;
  }

}
