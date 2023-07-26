<?php

namespace Drupal\shorthand\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'shorthand_local' field type.
 *
 * @FieldType(
 *   id = "shorthand_local",
 *   label = @Translation("Shorthand select"),
 *   description = @Translation("Select from downloaded Shorthand stories."),
 *   module = "shorthand",
 *   category = @Translation("Reference"),
 *   default_widget = "shorthand_local_story_select",
 *   default_formatter = "shorthand_local_story_render"
 *
 * )
 */
class LocalShorthandFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')->setLabel(t('Path'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /*public function
  fieldSettingsForm(array $form, FormStateInterface $form_state) {

  $options = [];
  // List downloaded stories.
  $destination_uri = 'public://' .
  RemoteCollectionController::SHORTHAND_STORY_BASE_PATH;

  $storyFolders = $this->fileSystem->scanDirectory($destination_uri, '\/.*\/', [
  'recurse' => FALSE,
  'key' => 'filename',
  //'key' => 'uri',
  ]);

  $options = array_keys($storyFolders);

  $element = [];
  // The key of the element should be the setting name.
  $element['path'] = [
  '#title' => $this->t('Path'),
  '#type' => 'select',
  '#options' => $options,
  '#default_value' => $this->getSetting('path'),
  ];

  return $element;
  }*/

}
