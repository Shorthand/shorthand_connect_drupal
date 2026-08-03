<?php

namespace Drupal\shorthand\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\shorthand\ShorthandApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides autocomplete callbacks for Shorthand stories.
 */
class StoryAutocompleteController extends ControllerBase {

  /**
   * Number of stories to fetch for autocomplete suggestions.
   */
  const AUTOCOMPLETE_LIMIT = 20;

  /**
   * Shorthand Api service.
   *
   * @var \Drupal\shorthand\ShorthandApiInterface
   */
  protected $shorthandApi;

  /**
   * Constructs a new StoryAutocompleteController object.
   *
   * @param \Drupal\shorthand\ShorthandApiInterface $shorthand_api
   *   The shorthand api connector.
   */
  public function __construct(ShorthandApiInterface $shorthand_api) {
    $this->shorthandApi = $shorthand_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('shorthand_api'));
  }

  /**
   * Return matching Shorthand stories.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Autocomplete suggestions.
   */
  public function stories(Request $request) {
    $keyword = trim((string) $request->query->get('q', ''));
    $query = [
      'limit' => self::AUTOCOMPLETE_LIMIT,
    ];

    if ($keyword !== '') {
      $query['keyword'] = $keyword;
    }

    $matches = [];
    $stories = $this->shorthandApi->getStories($query);
    if (is_array($stories)) {
      foreach ($stories as $story) {
        $title = $story['title'] ?? $story['id'];
        $id = $story['id'];
        $matches[] = [
          'value' => $title . ' (' . $id . ')',
          'label' => Html::escape($title . ' (' . $id . ')'),
        ];
      }
    }

    return new JsonResponse($matches);
  }

}
