<?php

namespace Drupal\shorthand\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\shorthand\ShorthandApiInterface;
use Drupal\shorthand\ShorthandStreamWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * The shorthand stream wrapper service.
   *
   * @var \Drupal\shorthand\ShorthandStreamWrapper
   */
  protected $streamWrapper;

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
   * @param \Drupal\shorthand\ShorthandStreamWrapper $stream_wrapper
   *   The shorthand stream wrapper service.
   */
  public function __construct(AccountInterface $current_user, ShorthandApiInterface $shorthand_api, FileSystemInterface $file_system, RendererInterface $renderer, MessengerInterface $messenger, ShorthandStreamWrapper $stream_wrapper = NULL) {
    $this->currentUser = $current_user;
    $this->shorthandApi = $shorthand_api;
    $this->fileSystem = $file_system;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
    // Make stream wrapper optional for backward compatibility.
    $this->streamWrapper = $stream_wrapper ?: \Drupal::service('shorthand.stream_wrapper');
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
      $container->get('shorthand.stream_wrapper')
    );
  }

  /**
   * Download shorthand stories.
   *
   * @param array $sids
   *   List of shorthand stories IDs.
   * @param array $context
   *   Batch content configuration.
   */
  public static function downloadStoryBatch(array $sids, array &$context) {
    $message = 'Downloading story...';
    $apiServiceName = 'shorthand_api';
    $apiService = \Drupal::service($apiServiceName);

    $results = [];
    $stories = [];
    $storiesApi = $apiService->getStories();
    foreach ($storiesApi as $storyApi) {
      $stories[$storyApi['id']] = $storyApi['updated'];
    }

    foreach ($sids as $sid) {
      $file = $apiService->getStory($sid, []);
      $file_system = \Drupal::service('file_system');
      $zip_realpath = $file_system->realpath($file);
      $archiver = \Drupal::service('plugin.manager.archiver')
        ->getInstance(['filepath' => $zip_realpath]);

      $timestamp = $stories[$sid];
      $stream_wrapper_service = \Drupal::service('shorthand.stream_wrapper');

      // Destination in the configured stream wrapper (e.g., s3:// or public://).
      $destination_uri = $stream_wrapper_service->getStorageUri(static::SHORTHAND_STORY_BASE_PATH . '/' . $sid . '/' . $timestamp);
      // Ensure destination directory exists (URI-based; works with wrappers).
      $file_system->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY);

      // Extract into a temporary local directory, then copy into destination URI.
      $temp_extract_dir_uri = 'temporary://shorthand_extract/' . $sid . '/' . $timestamp;
      $file_system->prepareDirectory($temp_extract_dir_uri, FileSystemInterface::CREATE_DIRECTORY);
      $temp_extract_dir_real = $file_system->realpath($temp_extract_dir_uri);

      $result = $archiver->extract($temp_extract_dir_real);

      if ($result) {
        // Recursively copy extracted files from local temp to destination URI.
        // Create subdirectories first, then copy files.
        $iterator = new \RecursiveIteratorIterator(
          new \RecursiveDirectoryIterator($temp_extract_dir_real, \FilesystemIterator::SKIP_DOTS),
          \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
          $relative_path = ltrim(str_replace($temp_extract_dir_real, '', $item->getPathname()), DIRECTORY_SEPARATOR);
          $target_uri = rtrim($destination_uri, '/') . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relative_path);
          if ($item->isDir()) {
            $file_system->prepareDirectory($target_uri, FileSystemInterface::CREATE_DIRECTORY);
          }
          else {
            // Ensure parent dir exists.
            $parent_dir = dirname($target_uri);
            $file_system->prepareDirectory($parent_dir, FileSystemInterface::CREATE_DIRECTORY);
            $file_system->copy($item->getPathname(), $target_uri, FileSystemInterface::EXISTS_REPLACE);
          }
        }
      }

      // Cleanup temporary extraction and local zip file.
      $file_system->deleteRecursive($temp_extract_dir_uri);
      $file_system->delete($zip_realpath);

      $results[] = (bool) $result;
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
    $stories = $this->shorthandApi->getStories();

    if (is_array($stories) && count($stories) === 0) {
      $this->messenger->addWarning($this->t('There are no stories to retrieve from Shorthand.'));
    }

    if (!$stories) {
      return [];
    }

    // List downloaded stories.
    $destination_uri = $this->streamWrapper->getStorageUri(static::SHORTHAND_STORY_BASE_PATH);

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
      '#type' => 'textfield',
      '#id' => 'story_filter',
      '#placeholder' => $this->t('Filter Stories'),
    ];

    foreach ($stories as $story) {
      unset($story['metadata']);
      unset($story['api_version']);

      $url = $story['image'];
      $title = $story['title'];
      $image_variables = [
        '#theme' => 'image',
        '#uri' => $url,
        '#alt' => $title,
        '#title' => $title,
        '#attributes' => [
          'class' => ['shorthand-story-image'],
        ],
      ];
      $story['image'] = $this->renderer->render($image_variables);

      $title = $this->t('Download story');
      $type = 'link';
      if (in_array($story['id'], $localStories)) {
        // Consider a story up to date if its article exists for the latest
        // timestamped version.
        $version_article_uri = $this->streamWrapper->getStorageUri(static::SHORTHAND_STORY_BASE_PATH . '/' . $story['id'] . '/' . $story['updated'] . '/article.html');
        if (file_exists($version_article_uri)) {
          $title = $this->t('The story is up to date');
          $type = 'markup';
        }
        else {
          $title = $this->t('Update story');
        }
      }

      $story['actions'] = [
        'data' => [
          'label' => [
            'data' => [
              'link' => [
                '#title' => $title,
                '#type' => $type,
                '#url' => Url::fromRoute('shorthand.download.story', [
                  'storyid' => $story['id'],
                ]),
              ],
            ],
          ],
        ],
      ];

      $rows[] = $story;
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

    $batch = [
      'title' => $this->t('Downloading story...'),
      'init_message' => $this->t('Downloading story...'),
      'error_message' => $this->t('An unrecoverable error has occurred.'),
      'operations' => [
        [
          'Drupal\shorthand\Controller\RemoteCollectionController::downloadStoryBatch',
          [[$storyid]],
        ],
      ],
      'finished' => 'Drupal\shorthand\Controller\RemoteCollectionController::downloadStoryComplete',
    ];

    batch_set($batch);
    return batch_process('/admin/content/shorthand');
  }

}
