<?php

namespace Drupal\shorthand\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\shorthand\LocalStoryIndex;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides autocomplete and detail callbacks for local Shorthand stories.
 */
class LocalStoryController extends ControllerBase {

  /**
   * Number of stories to return for autocomplete suggestions.
   */
  const AUTOCOMPLETE_LIMIT = 20;

  /**
   * Number of recent stories suggested before a keyword is typed.
   */
  const RECENT_LIMIT = 5;

  /**
   * The local story index.
   *
   * @var \Drupal\shorthand\LocalStoryIndex
   */
  protected $localStoryIndex;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new LocalStoryController object.
   *
   * @param \Drupal\shorthand\LocalStoryIndex $local_story_index
   *   The local story index.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(LocalStoryIndex $local_story_index, DateFormatterInterface $date_formatter) {
    $this->localStoryIndex = $local_story_index;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shorthand.local_story_index'),
      $container->get('date.formatter')
    );
  }

  /**
   * Return matching locally downloaded Shorthand stories.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Autocomplete suggestions.
   */
  public function autocomplete(Request $request) {
    $keyword = trim((string) $request->query->get('q', ''));

    // Before a keyword is typed, suggest the most recently
    // downloaded/updated stories rather than an alphabetical slice.
    $stories = $keyword === ''
      ? $this->localStoryIndex->recent(self::RECENT_LIMIT)
      : $this->localStoryIndex->search($keyword, self::AUTOCOMPLETE_LIMIT);

    $matches = [];
    foreach ($stories as $story) {
      $matches[] = [
        'value' => $story['title'] . ' (' . $story['id'] . ')',
        'label' => Html::escape($story['title'] . ' (' . $story['id'] . ')'),
        'id' => $story['id'],
        'title' => $story['title'],
        'updated' => $this->formatVersionLabel($story['versions'][0]),
        'image' => $story['image'],
        'thumbnail' => Url::fromRoute('shorthand.local_thumbnail', [
          'story_id' => $story['id'],
          'version_id' => $story['versions'][0],
        ])->toString(),
      ];
    }

    return new JsonResponse($matches);
  }

  /**
   * Return detail data for one locally downloaded Shorthand story.
   *
   * @param string $story_id
   *   The Shorthand story ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Story detail data for the selection widget.
   */
  public function detail($story_id) {
    $story = $this->localStoryIndex->get($story_id);
    if ($story === NULL) {
      throw new NotFoundHttpException();
    }

    $versions = [];
    foreach ($story['versions'] as $version_id) {
      $versions[] = [
        'id' => $version_id,
        'label' => $this->formatVersionLabel($version_id),
      ];
    }

    return new JsonResponse([
      'id' => $story['id'],
      'title' => $story['title'],
      'description' => $story['description'],
      'image' => $story['image'],
      'thumbnail' => Url::fromRoute('shorthand.local_thumbnail', [
        'story_id' => $story['id'],
        'version_id' => $story['versions'][0],
      ])->toString(),
      'versions' => $versions,
    ]);
  }

  /**
   * Render a locally downloaded story version as a standalone page.
   *
   * Used as the src of the selection widget's preview iframe. Applies the
   * same relative-asset rewriting as LocalShorthandFieldFormatter.
   *
   * @param string $story_id
   *   The Shorthand story ID.
   * @param string $version_id
   *   The downloaded story version ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The rendered story page.
   */
  public function preview($story_id, $version_id) {
    if (!$this->isValidPathSegment($story_id) || !$this->isValidPathSegment($version_id)) {
      throw new NotFoundHttpException();
    }

    $path = $story_id . '/' . $version_id;
    $base_uri = 'public://' . RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path;
    if (!file_exists($base_uri . '/article.html')) {
      throw new NotFoundHttpException();
    }

    $article = file_get_contents($base_uri . '/article.html');
    $head = file_exists($base_uri . '/head.html') ? file_get_contents($base_uri . '/head.html') : '';

    $public = PublicStream::basePath();
    foreach (['assets', 'static'] as $folder) {
      $url = Url::fromUserInput('/' . $public . '/' . RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path . '/' . $folder . '/', [
        'absolute' => TRUE,
      ])->toString();
      $article = str_replace('./' . $folder . '/', $url, $article);
      $head = str_replace('./' . $folder . '/', $url, $head);
    }

    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
      . $head
      . '</head><body>'
      . $article
      . '</body></html>';

    $response = new Response($html);
    $response->headers->set('Content-Type', 'text/html; charset=utf-8');
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
   * Format a downloaded version ID as a readable date.
   *
   * @param string $version_id
   *   The version ID (an ISO 8601 timestamp).
   *
   * @return string
   *   The formatted label.
   */
  protected function formatVersionLabel($version_id) {
    $timestamp = strtotime($version_id);
    return $timestamp !== FALSE ? $this->dateFormatter->format($timestamp, 'short') : $version_id;
  }

}
