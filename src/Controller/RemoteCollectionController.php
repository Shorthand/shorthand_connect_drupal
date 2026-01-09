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
   */
  public function __construct(AccountInterface $current_user, ShorthandApiInterface $shorthand_api, FileSystemInterface $file_system, RendererInterface $renderer, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->shorthandApi = $shorthand_api;
    $this->fileSystem = $file_system;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
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
      $container->get('messenger')
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
      $filepath = $file_system->realpath($file);
      $archiver = \Drupal::service('plugin.manager.archiver')
        ->getInstance(['filepath' => $filepath]);

      $timestamp = $stories[$sid];
      $destination_uri = 'public://' . static::SHORTHAND_STORY_BASE_PATH . '/' . $sid . '/' . $timestamp;
      $file_system->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY);
      $destination_path = $file_system->realpath($destination_uri);
      $result = $archiver->extract($destination_path);
      $file_system->delete($filepath);

      $config = \Drupal::config('shorthand.settings');
      $keep_previous_versions = $config->get('keep_previous_versions');
      if ($keep_previous_versions === NULL) {
        $keep_previous_versions = TRUE;
      }
      if (!$keep_previous_versions) {
        $entity_field_manager = \Drupal::service('entity_field.manager');
        $entity_type_manager = \Drupal::entityTypeManager();
        $field_map = $entity_field_manager->getFieldMapByFieldType('shorthand_local');
        $story_prefix = $sid . '/';
        foreach ($field_map as $entity_type_id => $fields) {
          $storage = $entity_type_manager->getStorage($entity_type_id);
          foreach ($fields as $field_name => $field_info) {
            $query = $storage->getQuery()
              ->condition($field_name . '.value', $story_prefix, 'STARTS_WITH')
              ->accessCheck(FALSE);
            $entity_ids = $query->execute();
            if (empty($entity_ids)) {
              continue;
            }
            $entities = $storage->loadMultiple($entity_ids);
            foreach ($entities as $entity) {
              $updated = FALSE;
              foreach ($entity->get($field_name) as $item) {
                if (is_string($item->value) && strpos($item->value, $story_prefix) === 0) {
                  $item->value = $sid . '/' . $timestamp;
                  $updated = TRUE;
                }
              }
              if ($updated) {
                $entity->save();
              }
            }
          }
        }

        $story_versions = $file_system->scanDirectory('public://' . static::SHORTHAND_STORY_BASE_PATH . '/' . $sid, '/.*/', [
          'recurse' => FALSE,
          'key' => 'filename',
        ]);
        foreach (array_keys($story_versions) as $version_id) {
          if ($version_id !== $timestamp) {
            $file_system->deleteRecursive('public://' . static::SHORTHAND_STORY_BASE_PATH . '/' . $sid . '/' . $version_id);
          }
        }
      }

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
    $can_delete = $this->currentUser->hasPermission('delete shorthand content');

    $input = [
      '#type' => 'textfield',
      '#id' => 'story_filter',
      '#placeholder' => $this->t('Filter Stories'),
    ];

    foreach ($stories as $story) {
      unset($story['metadata']);
      unset($story['api_version']);
      unset($story['external_url']);

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

        $path = $this->fileSystem->realpath('public://' . static::SHORTHAND_STORY_BASE_PATH . '/' . $story['id'] . '/' . $story['updated']);
        if (file_exists($path)) {
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

      $story['manage_versions'] = [
        'data' => $can_delete && in_array($story['id'], $localStories) ? [
          '#type' => 'link',
          '#title' => $this->t('Manage versions'),
          '#url' => Url::fromRoute('shorthand.manage_story_versions', [
            'storyid' => $story['id'],
          ]),
          '#attributes' => [
            'class' => ['use-ajax', 'button', 'button--small'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode(['width' => 600]),
          ],
        ] : [
          '#markup' => $this->t('None'),
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
      'Manage',
      'Action',
    ];

    return [
      '#type' => 'page',
      'content' => [
        'filter_input' => $input,
        'delete_unused' => $can_delete ? [
          '#type' => 'link',
          '#title' => $this->t('Delete unused versions'),
          '#url' => Url::fromRoute('shorthand.delete.unused_versions'),
          '#attributes' => [
            'class' => ['button', 'button--danger', 'use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode(['width' => 600]),
          ],
        ] : NULL,
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
          'core/drupal.dialog.ajax',
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

  /**
   * Delete a local story version.
   *
   * @param string $storyid
   *   Shorthand story ID.
   * @param string $version
   *   Shorthand story version.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   */
  public function deleteStoryVersion($storyid = NULL, $version = NULL) {
    if (empty($storyid) || empty($version)) {
      throw new AccessDeniedHttpException();
    }

    $used_versions = $this->getUsedStoryVersions();
    if (!empty($used_versions[$storyid][$version])) {
      $this->messenger->addError($this->t('Cannot delete version @version because it is in use.', [
        '@version' => $version,
      ]));
      return $this->redirect('shorthand.remote_collection');
    }

    $folder = 'public://' . static::SHORTHAND_STORY_BASE_PATH . '/' . $storyid . '/' . $version;
    $this->fileSystem->deleteRecursive($folder);
    $story_folder = 'public://' . static::SHORTHAND_STORY_BASE_PATH . '/' . $storyid;
    $remaining_versions = $this->fileSystem->scanDirectory($story_folder, '/.*/', [
      'recurse' => FALSE,
      'key' => 'filename',
    ]);
    if (empty($remaining_versions)) {
      $this->fileSystem->deleteRecursive($story_folder);
    }
    $this->messenger->addStatus($this->t('Deleted shorthand version @version.', [
      '@version' => $version,
    ]));

    return $this->redirect('shorthand.remote_collection');
  }

  /**
   * Delete all unused local story versions.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   */
  public function deleteUnusedStoryVersions() {
    $destination_uri = 'public://' . static::SHORTHAND_STORY_BASE_PATH;
    if (!$this->fileSystem->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY)) {
      $this->messenger->addWarning($this->t('Error accessing shorthand stories folder.'));
      return $this->redirect('shorthand.remote_collection');
    }

    $used_versions = $this->getUsedStoryVersions();
    $storyFolders = $this->fileSystem->scanDirectory($destination_uri, '/.*/', [
      'recurse' => FALSE,
      'key' => 'filename',
    ]);
    foreach (array_keys($storyFolders) as $story_id) {
      $storyVersionFolders = $this->fileSystem->scanDirectory($destination_uri . '/' . $story_id, '/.*/', [
        'recurse' => FALSE,
        'key' => 'filename',
      ]);
      foreach (array_keys($storyVersionFolders) as $version_id) {
        if (empty($used_versions[$story_id][$version_id])) {
          $this->fileSystem->deleteRecursive($destination_uri . '/' . $story_id . '/' . $version_id);
        }
      }
      $remaining_versions = $this->fileSystem->scanDirectory($destination_uri . '/' . $story_id, '/.*/', [
        'recurse' => FALSE,
        'key' => 'filename',
      ]);
      if (empty($remaining_versions)) {
        $this->fileSystem->deleteRecursive($destination_uri . '/' . $story_id);
      }
    }

    $this->messenger->addStatus($this->t('Deleted all unused shorthand versions.'));
    return $this->redirect('shorthand.remote_collection');
  }

  /**
   * Build a map of used local shorthand versions.
   *
   * @return array
   *   Array keyed by story ID and version ID.
   */
  protected function getUsedStoryVersions() {
    $used = [];
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $entity_type_manager = \Drupal::entityTypeManager();
    $field_map = $entity_field_manager->getFieldMapByFieldType('shorthand_local');

    foreach ($field_map as $entity_type_id => $fields) {
      $storage = $entity_type_manager->getStorage($entity_type_id);
      foreach ($fields as $field_name => $field_info) {
        $query = $storage->getQuery()
          ->condition($field_name . '.value', NULL, 'IS NOT NULL')
          ->accessCheck(FALSE);
        $entity_ids = $query->execute();
        if (empty($entity_ids)) {
          continue;
        }
        $entities = $storage->loadMultiple($entity_ids);
        foreach ($entities as $entity) {
          foreach ($entity->get($field_name) as $item) {
            if (!is_string($item->value)) {
              continue;
            }
            $parts = explode('/', $item->value, 2);
            if (count($parts) === 2) {
              $used[$parts[0]][$parts[1]] = TRUE;
            }
          }
        }
      }
    }

    return $used;
  }

}
