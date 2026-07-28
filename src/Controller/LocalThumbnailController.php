<?php

namespace Drupal\shorthand\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\shorthand\LocalStoryIndex;
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
   * The local story index.
   *
   * @var \Drupal\shorthand\LocalStoryIndex
   */
  protected $localStoryIndex;

  /**
   * Constructs a new LocalThumbnailController object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\shorthand\LocalStoryIndex $local_story_index
   *   The local story index.
   */
  public function __construct(FileSystemInterface $file_system, LocalStoryIndex $local_story_index) {
    $this->fileSystem = $file_system;
    $this->localStoryIndex = $local_story_index;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('shorthand.local_story_index')
    );
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

    $metadata = $this->localStoryIndex->extractHeadMetadata($story_version_uri . '/head.html');
    $local_uri = $this->localStoryIndex->resolveLocalImageUri($metadata['image'], $story_version_uri);
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

}
