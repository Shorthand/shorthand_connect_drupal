<?php

namespace Drupal\shorthand\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Shorthand story entity.
 *
 * @ingroup shorthand
 *
 * @ContentEntityType(
 *   id = "shorthand_story",
 *   label = @Translation("Shorthand story"),
 *   handlers = {
 *     "storage" = "Drupal\shorthand\ShorthandStoryStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\shorthand\ShorthandStoryListBuilder",
 *     "views_data" = "Drupal\shorthand\Entity\ShorthandStoryViewsData",
 *     "translation" = "Drupal\shorthand\ShorthandStoryTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\shorthand\Form\ShorthandStoryForm",
 *       "add" = "Drupal\shorthand\Form\ShorthandStoryForm",
 *       "edit" = "Drupal\shorthand\Form\ShorthandStoryForm",
 *       "delete" = "Drupal\shorthand\Form\ShorthandStoryDeleteForm",
 *     },
 *     "access" = "Drupal\shorthand\ShorthandStoryAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\shorthand\ShorthandStoryHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "shorthand_story",
 *   data_table = "shorthand_story_field_data",
 *   revision_table = "shorthand_story_revision",
 *   revision_data_table = "shorthand_story_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer shorthand story entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/shorthand-story/{shorthand_story}",
 *     "add-form" = "/admin/content/shorthand-story/add",
 *     "edit-form" = "/admin/content/shorthand-story/{shorthand_story}/edit",
 *     "delete-form" = "/admin/content/shorthand-story/{shorthand_story}/delete",
 *     "version-history" = "/admin/content/shorthand-story/{shorthand_story}/revisions",
 *     "revision" = "/admin/content/shorthand-story/{shorthand_story}/revisions/{shorthand_story_revision}/view",
 *     "revision_revert" = "/admin/content/shorthand-story/{shorthand_story}/revisions/{shorthand_story_revision}/revert",
 *     "revision_delete" = "/admin/content/shorthand-story/{shorthand_story}/revisions/{shorthand_story_revision}/delete",
 *     "translation_revert" = "/admin/content/shorthand-story/{shorthand_story}/revisions/{shorthand_story_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/shorthand-story",
 *   },
 *   field_ui_base_route = "shorthand_story.settings"
 * )
 */
class ShorthandStory extends RevisionableContentEntityBase implements ShorthandStoryInterface {

  use EntityChangedTrait;

  /**
   * Defines shorthand's stories container base path.
   */
  const SHORTHAND_STORY_BASE_PATH = 'shorthand/stories';

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {

    $version = $this->getShorthandAPIVersion();
    $apiservice = 'shorthand.api';
    $head_file = '/component_head.html';
    $body_file = '/component_article.html';

    if ($version == '2') {
      $apiservice = 'shorthand.api.v2';
      $head_file = '/head.html';
      $body_file = '/article.html';
    }

    // Download and extract Story .zip file.
    if ($this->isNew()) {
      $file = \Drupal::service($apiservice)->getStory($this->getShorthandStoryId());
      $input_format = \Drupal::service('settings')->get('shorthand_input_format', filter_default_format());

      /** @var \Drupal\Core\Archiver\ArchiverInterface $archiver */
      $archiver = archiver_get_archiver($file);

      $destination_uri = $this->getShorthandStoryFilesStorageUri();
      file_prepare_directory($destination_uri, FILE_CREATE_DIRECTORY);

      $destination_path = \Drupal::service('file_system')->realpath($destination_uri);
      $archiver->extract($destination_path);

      // Store head and body, handling text in any language.
      $head = mb_convert_encoding(
        file_get_contents($destination_path . $head_file),
        "HTML-ENTITIES",
        "UTF-8"
      );
      $this->head->value = $this->fixStoryContentPaths($head, $version);
      $this->head->format = $input_format;

      $body = mb_convert_encoding(
        file_get_contents($destination_path . $body_file),
        "HTML-ENTITIES",
        "UTF-8"
      );
      $this->body->value = $this->fixStoryContentPaths($body, $version);
      $this->body->format = $input_format;
    }

    // Let parent preSave() run so other modules can alter the content before
    // being saved.
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the shorthand_story
    // owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getShorthandStoryId() {
    return $this->get('shorthand_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->get('body')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHead() {
    return $this->get('head')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['shorthand_id'] = BaseFieldDefinition::create('shorthand_story_id')
      ->setLabel(t('Story ID'))
      ->setDescription(t('Shorthand Story ID.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'shorthand_story_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Shorthand story entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Shorthand story entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Story'))
      ->setDescription(t('The body of the story as coming from Shorthand .zip file.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Head exists as a field, but hidden from any display.
    $fields['head'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Head'))
      ->setDescription(t('The html head of the story as coming from Shorthand .zip file.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Shorthand story is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();

    // Delete Shorthand story files.
    file_unmanaged_delete_recursive($this->getShorthandStoryFilesStorageUri());
  }

  /**
   * Returns shorthand story URI.
   *
   * @return string
   *   The URI where shorthand story .zip file has been extracted.
   */
  public function getShorthandStoryFilesStorageUri() {
    return 'public://' . self::SHORTHAND_STORY_BASE_PATH . '/' . $this->getShorthandStoryId() . '/' . $this->uuid();
  }

  /**
   * Get the API version of Shorthand
   * @return string
   *   The version of the configured Shorthand API
   */
  protected function getShorthandAPIVersion() {
    return \Drupal::service('settings')->get('shorthand_version', '1');
  }

  /**
   * Fixes paths in the shorthand story.
   *
   * @param string $content
   *   Shorthand Story's HTML markup to be processed.
   *
   * @return string
   *   Content processed with all path relative to Drupal's Shorthand story
   *   storage path.
   */
  protected function fixStoryContentPaths($content, $version='1') {
    $assets_path = file_create_url($this->getShorthandStoryFilesStorageUri());
    if ($version == '2') {
      $content = str_replace('./assets/', $assets_path.'/assets/', $content);
      $content = str_replace('./static/', $assets_path.'/static/', $content);
      $content = preg_replace('/.(\/theme-\w+.min.css)/', $assets_path.'$1', $content);
    } else {
      $content = str_replace('./static/', $assets_path.'/static/', $content);
      $content = str_replace('./media/', $assets_path.'/media/', $content);
    }
    return $content;
  }

}
