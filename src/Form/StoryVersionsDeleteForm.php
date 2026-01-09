<?php

namespace Drupal\shorthand\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a form to delete local shorthand story versions.
 */
class StoryVersionsDeleteForm extends FormBase {

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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorthand_story_versions_delete_form';
  }

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
  public function buildForm(array $form, FormStateInterface $form_state, $storyid = NULL) {
    if (empty($storyid)) {
      throw new AccessDeniedHttpException();
    }

    $destination_uri = 'public://shorthand/stories/' . $storyid;
    if (!$this->fileSystem->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY)) {
      $this->messenger()->addWarning($this->t('Error accessing shorthand stories folder.'));
      return $form;
    }

    $storyVersionFolders = $this->fileSystem->scanDirectory($destination_uri, '/.*/', [
      'recurse' => FALSE,
      'key' => 'filename',
    ]);

    $versions = array_keys($storyVersionFolders);
    if (empty($versions)) {
      $form['message'] = [
        '#markup' => $this->t('No local versions found for this story.'),
      ];
      return $form;
    }

    $used_versions = $this->getUsedStoryVersions();
    $options = [];
    $option_attributes = [];
    foreach ($versions as $version_id) {
      $in_use = !empty($used_versions[$storyid][$version_id]);
      $status = $in_use ? $this->t('In use') : $this->t('Not in use');
      $options[$version_id] = $this->t('@version (@status)', [
        '@version' => $version_id,
        '@status' => $status,
      ]);
      if ($in_use) {
        $option_attributes[$version_id] = ['disabled' => 'disabled'];
      }
    }

    $form['#attributes']['class'][] = 'shorthand-version-delete-form';
    $form['#attached']['library'][] = 'shorthand/shorthandForm';

    $form['storyid'] = [
      '#type' => 'hidden',
      '#value' => $storyid,
    ];

    $form['select_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select all'),
      '#attributes' => [
        'class' => ['shorthand-select-all'],
      ],
    ];

    $form['versions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Versions'),
      '#options' => $options,
      '#option_attributes' => $option_attributes,
      '#attributes' => [
        'class' => ['shorthand-version-options'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected'),
      '#button_type' => 'danger',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storyid = $form_state->getValue('storyid');
    $versions = array_filter($form_state->getValue('versions') ?? []);
    if (empty($versions)) {
      $this->messenger()->addWarning($this->t('No versions selected.'));
      return;
    }

    $used_versions = $this->getUsedStoryVersions();
    foreach ($versions as $version_id) {
      if (!empty($used_versions[$storyid][$version_id])) {
        continue;
      }
      $folder = 'public://shorthand/stories/' . $storyid . '/' . $version_id;
      $this->fileSystem->deleteRecursive($folder);
    }

    $story_folder = 'public://shorthand/stories/' . $storyid;
    $remaining_versions = $this->fileSystem->scanDirectory($story_folder, '/.*/', [
      'recurse' => FALSE,
      'key' => 'filename',
    ]);
    if (empty($remaining_versions)) {
      $this->fileSystem->deleteRecursive($story_folder);
    }

    $this->messenger()->addStatus($this->t('Selected versions deleted.'));
    $form_state->setRedirect('shorthand.remote_collection');
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
