<?php

namespace Drupal\shorthand\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\shorthand\ShorthandApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Configure shorthand settings for this site.
 */
class RemoteCollectionController extends ControllerBase {

  /**
   * Defines shorthand stories container base path.
   */
  const SHORTHAND_STORY_BASE_PATH = 'shorthand/stories';

  /**
   * Number of remote stories to request per page by default.
   */
  const DEFAULT_STORIES_LIMIT = 100;

  /**
   * Maximum number of remote stories to request per page.
   */
  const MAX_STORIES_LIMIT = 250;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Shorthand Api service.
   *
   * @var \Drupal\shorthand\ShorthandApiInterface
   */
  protected $shorthandApi;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The constructor method.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\shorthand\ShorthandApiInterface $shorthand_api
   *   The shorthand api connector.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger interface.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(AccountInterface $current_user, ShorthandApiInterface $shorthand_api, FileSystemInterface $file_system, RendererInterface $renderer, MessengerInterface $messenger, RequestStack $request_stack) {
    $this->currentUser = $current_user;
    $this->shorthandApi = $shorthand_api;
    $this->fileSystem = $file_system;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('shorthand_api'),
      $container->get('file_system'),
      $container->get('renderer'),
      $container->get('messenger'),
      $container->get('request_stack')
    );
  }

  /**
   * Download shorthand stories.
   *
   * @param array $sids
   *   List of shorthand stories IDs.
   * @param array $story_versions
   *   List of Shorthand story updated timestamps keyed by story ID.
   * @param array $context
   *   Batch content configuration.
   */
  public static function downloadStoryBatch(array $sids, array $story_versions, array &$context) {
    $message = 'Downloading story...';
    $apiServiceName = 'shorthand_api';
    $apiService = \Drupal::service($apiServiceName);

    $results = [];
    $stories = [];
    foreach ($story_versions as $story_id => $updated) {
      if (!empty($updated)) {
        $stories[$story_id] = $updated;
      }
    }

    if (empty($stories)) {
      $storiesApi = $apiService->getStories();
      if (is_array($storiesApi)) {
        foreach ($storiesApi as $storyApi) {
          $stories[$storyApi['id']] = $storyApi['updated'];
        }
      }
    }

    foreach ($sids as $sid) {
      $file = $apiService->getStory($sid, []);
      $file_system = \Drupal::service('file_system');
      $filepath = $file_system->realpath($file);
      $archiver = \Drupal::service('plugin.manager.archiver')
        ->getInstance(['filepath' => $filepath]);

      $timestamp = $stories[$sid] ?? date('c');
      $destination_uri = 'public://' . static::SHORTHAND_STORY_BASE_PATH . '/' . $sid . '/' . $timestamp;
      $file_system->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY);
      $destination_path = $file_system->realpath($destination_uri);
      $result = $archiver->extract($destination_path);
      $file_system->delete($filepath);

      $results[] = $result;
    }

    $context['message'] = $message;
    $context['results'] = $results;
  }

  /**
   * Callback to finish batch processing.
   */
  public static function downloadStoryComplete($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    $message = "";

    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results), 'One story downloaded.', '@count stories downloaded.'
      );
    }
    else {
      $message = 'Finished with an error.';
    }

    \Drupal::service('shorthand.local_story_index')->invalidate();
    \Drupal::messenger()->addStatus($message);
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function list() {
    $rows = [];
    $request = $this->requestStack->getCurrentRequest();
    $keyword = trim((string) $request->query->get('keyword', ''));
    $cursor = trim((string) $request->query->get('cursor', ''));
    $limit = (int) $request->query->get('limit', self::DEFAULT_STORIES_LIMIT);
    if ($limit <= 0) {
      $limit = self::DEFAULT_STORIES_LIMIT;
    }
    $limit = min($limit, self::MAX_STORIES_LIMIT);

    $story_query = [
      'limit' => $limit + 1,
    ];
    if ($cursor !== '') {
      $story_query['cursor'] = $cursor;
    }
    if ($keyword !== '') {
      $story_query['keyword'] = $keyword;
    }

    $stories = $this->shorthandApi->getStories($story_query);

    if ($stories === FALSE) {
      return [];
    }

    $has_next_page = count($stories) > $limit;
    if ($has_next_page) {
      $stories = array_slice($stories, 0, $limit);
    }

    if (count($stories) === 0) {
      $this->messenger->addWarning($this->t('There are no stories to retrieve from Shorthand.'));
    }

    // List downloaded stories.
    $destination_uri = 'public://' . static::SHORTHAND_STORY_BASE_PATH;

    if (!$this->fileSystem->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY)) {
      $this->messenger->addWarning($this->t('Error accessing shorthand stories folder.'));
      return [];
    }

    $storyFolders = $this->fileSystem->scanDirectory($destination_uri, '/.*/', [
      'recurse' => FALSE,
      'key' => 'filename',
    ]);

    $localStories = array_keys($storyFolders);

    $input = [
      '#type' => 'form',
      '#method' => 'get',
      '#action' => Url::fromRoute('shorthand.remote_collection')->toString(),
      '#attributes' => [
        'class' => ['shorthand-story-filter-form'],
      ],
      'keyword' => [
        '#type' => 'search',
        '#id' => 'story_filter',
        '#name' => 'keyword',
        '#title' => $this->t('Search stories'),
        '#title_display' => 'invisible',
        '#default_value' => $keyword,
        '#placeholder' => $this->t('Search stories'),
      ],
      'limit' => [
        '#type' => 'select',
        '#name' => 'limit',
        '#title' => $this->t('Stories per page'),
        '#options' => [
          50 => 50,
          100 => 100,
          250 => 250,
        ],
        '#default_value' => $limit,
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Search'),
        ],
      ],
    ];

    foreach ($stories as $story) {
      unset($story['metadata']);
      unset($story['api_version']);

      $url = $story['image'];
      $title = $story['title'];
      if (!empty($url)) {
        $image_variables = [
          '#theme' => 'image',
          '#uri' => $url,
          '#alt' => $title,
          '#title' => $title,
          '#attributes' => [
            'class' => ['shorthand-story-image'],
          ],
        ];
      }
      else {
        $image_variables = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['shorthand-story-image-placeholder'],
            'aria-hidden' => 'true',
          ],
        ];
      }
      $story['image'] = $this->renderer->render($image_variables);

      $title = $this->t('Download story');
      $type = 'link';
      if (in_array($story['id'], $localStories)) {

        $path = $this->fileSystem->realpath('public://' . static::SHORTHAND_STORY_BASE_PATH . '/' . $story['id'] . '/' . $story['updated']);
        if (file_exists($path)) {
          $title = $this->t('The story is up to date');
          $type = 'markup';
        }
        else {
          $title = $this->t('Update story');
        }
      }

      if ($type === 'link') {
        $action = [
          '#title' => $title,
          '#type' => 'link',
          '#url' => Url::fromRoute('shorthand.download.story', [
            'storyid' => $story['id'],
          ], [
            'query' => [
              'updated' => $story['updated'],
            ],
          ]),
        ];
      }
      else {
        $action = [
          '#markup' => $title,
        ];
      }

      $story['actions'] = [
        'data' => [
          'label' => [
            'data' => [
              'link' => $action,
            ],
          ],
        ],
      ];

      $rows[] = $story;
    }

    $pagination = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['shorthand-story-pagination'],
      ],
    ];

    if ($cursor !== '') {
      $first_query = [
        'limit' => $limit,
      ];
      if ($keyword !== '') {
        $first_query['keyword'] = $keyword;
      }

      $pagination['first'] = [
        '#type' => 'link',
        '#title' => $this->t('First page'),
        '#url' => Url::fromRoute('shorthand.remote_collection', [], [
          'query' => $first_query,
        ]),
        '#attributes' => [
          'class' => ['button'],
        ],
      ];
    }

    if ($has_next_page) {
      $last_story = end($stories);
      if (!empty($last_story['updated']) && !empty($last_story['id'])) {
        $next_query = [
          'limit' => $limit,
          'cursor' => base64_encode(json_encode([
            'updatedAt' => $last_story['updated'],
            'id' => $last_story['id'],
          ])),
        ];
        if ($keyword !== '') {
          $next_query['keyword'] = $keyword;
        }

        $pagination['next'] = [
          '#type' => 'link',
          '#title' => $this->t('Next page'),
          '#url' => Url::fromRoute('shorthand.remote_collection', [], [
            'query' => $next_query,
          ]),
          '#attributes' => [
            'class' => ['button'],
          ],
        ];
      }
    }

    $header = [
      'Image',
      'ID',
      'Title',
      'Status',
      'Published',
      'Updated',
      'External url',
      'Action',
    ];

    return [
      '#type' => 'page',
      'content' => [
        'filter_input' => $input,
        'story_list' => [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#attributes' => [
            'class' => ['shorthand-story-list'],
          ],
          '#header_columns' => 4,
        ],
        'pagination' => $pagination,
      ],
      '#attached' => [
        'library' => [
          'shorthand/shorthandForm',
        ],
      ],
    ];
  }

  /**
   * Download shorthand story.
   *
   * @param string $storyid
   *   Shorthand story ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function downloadStory($storyid = NULL) {
    if (empty($storyid)) {
      throw new AccessDeniedHttpException();
    }

    $updated = (string) $this->requestStack->getCurrentRequest()->query->get('updated', '');

    $batch = [
      'title' => $this->t('Downloading story...'),
      'init_message' => $this->t('Downloading story...'),
      'error_message' => $this->t('An unrecoverable error has occurred.'),
      'operations' => [
        [
          'Drupal\shorthand\Controller\RemoteCollectionController::downloadStoryBatch',
          [[$storyid], [$storyid => $updated]],
        ],
      ],
      'finished' => 'Drupal\shorthand\Controller\RemoteCollectionController::downloadStoryComplete',
    ];

    batch_set($batch);
    return batch_process('/admin/content/shorthand');
  }

}
