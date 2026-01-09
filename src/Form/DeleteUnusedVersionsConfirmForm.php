<?php

namespace Drupal\shorthand\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation form for deleting unused shorthand versions.
 */
class DeleteUnusedVersionsConfirmForm extends ConfirmFormBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Count of unused versions.
   *
   * @var int
   */
  protected int $unusedCount = 0;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->fileSystem = $container->get('file_system');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorthand_delete_unused_versions_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(
      $this->unusedCount,
      'Delete 1 unused version?',
      'Delete @count unused versions?'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This deletes unused local versions only. Versions currently linked to content will not be removed.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('shorthand.remote_collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->unusedCount = $this->countUnusedVersions();
    $form = parent::buildForm($form, $form_state);
    if ($this->unusedCount === 0) {
      $form['actions']['submit']['#disabled'] = TRUE;
      $form['message'] = [
        '#markup' => $this->t('No unused versions were found.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $deleted = $this->deleteUnusedVersions();
    if ($deleted > 0) {
      $this->messenger()->addStatus($this->formatPlural(
        $deleted,
        'Deleted 1 unused version.',
        'Deleted @count unused versions.'
      ));
    }
    else {
      $this->messenger()->addStatus($this->t('No unused versions were deleted.'));
    }

    $form_state->setRedirect('shorthand.remote_collection');
  }

  /**
   * Count all unused local story versions.
   *
   * @return int
   *   The number of unused versions.
   */
  protected function countUnusedVersions() {
    $destination_uri = 'public://shorthand/stories';
    if (!$this->fileSystem->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY)) {
      return 0;
    }

    $used_versions = $this->getUsedStoryVersions();
    $count = 0;
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
          $count++;
        }
      }
    }

    return $count;
  }

  /**
   * Delete all unused local story versions.
   *
   * @return int
   *   The number of deleted versions.
   */
  protected function deleteUnusedVersions() {
    $destination_uri = 'public://shorthand/stories';
    if (!$this->fileSystem->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY)) {
      return 0;
    }

    $used_versions = $this->getUsedStoryVersions();
    $deleted = 0;
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
          $deleted++;
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

    return $deleted;
  }

  /**
   * Build a map of used local shorthand versions.
   *
   * @return array
   *   Array keyed by story ID and version ID.
   */
  protected function getUsedStoryVersions() {
    $used = [];
    $field_map = $this->entityFieldManager->getFieldMapByFieldType('shorthand_local');

    foreach ($field_map as $entity_type_id => $fields) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
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
