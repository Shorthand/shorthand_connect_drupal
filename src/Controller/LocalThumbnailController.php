<?php

namespace Drupal\shorthand\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides locally downloaded story thumbnails.
 */
class LocalThumbnailController extends ControllerBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new LocalThumbnailController object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('file_system'));
  }

  /**
   * Return a locally downloaded thumbnail image.
   *
   * @param string $story_id
   *   The Shorthand story ID.
   * @param string $version_id
   *   The downloaded story version ID.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The local thumbnail image response.
   */
  public function thumbnail($story_id, $version_id) {
    if (!$this->isValidPathSegment($story_id) || !$this->isValidPathSegment($version_id)) {
      throw new NotFoundHttpException();
    }

    $story_version_uri = 'public://' . RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $story_id . '/' . $version_id;
    $story_version_path = $this->fileSystem->realpath($story_version_uri);
    if ($story_version_path === FALSE || !is_dir($story_version_path)) {
      throw new NotFoundHttpException();
    }

    $local_uri = $this->resolveLocalImageUri($this->extractHeadImage($story_version_uri . '/head.html'), $story_version_uri);
    if ($local_uri === '') {
      throw new NotFoundHttpException();
    }

    $local_path = $this->fileSystem->realpath($local_uri);
    if ($local_path === FALSE || !is_file($local_path) || !str_starts_with($local_path, $story_version_path . DIRECTORY_SEPARATOR)) {
      throw new NotFoundHttpException();
    }

    $response = new BinaryFileResponse($local_path);
    $response->headers->set('Content-Type', mime_content_type($local_path) ?: 'application/octet-stream');
    $response->headers->set('Cache-Control', 'private, max-age=300');
    return $response;
  }

  /**
   * Check that a route value is safe to use as a local path segment.
   *
   * @param string $segment
   *   The path segment.
   *
   * @return bool
   *   TRUE when the segment is safe.
   */
  protected function isValidPathSegment($segment) {
    return $segment !== '' && !str_contains($segment, '..') && !preg_match('#[\\\\/]#', $segment);
  }

  /**
   * Extract the social image URL from a downloaded head.html file.
   *
   * @param string $head_uri
   *   The local head.html URI.
   *
   * @return string
   *   The image URL, if found.
   */
  protected function extractHeadImage($head_uri) {
    if (!file_exists($head_uri)) {
      return '';
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

    return $meta['og:image'] ?? $meta['twitter:image'] ?? '';
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

  /**
   * Resolve a metadata image URL to a local downloaded file URI.
   *
   * @param string $image
   *   The image URL extracted from metadata.
   * @param string $story_version_uri
   *   The local story version URI.
   *
   * @return string
   *   The local file URI, if found.
   */
  protected function resolveLocalImageUri($image, $story_version_uri) {
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

}
