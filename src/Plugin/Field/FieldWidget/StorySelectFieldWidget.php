<?php

namespace Drupal\shorthand\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'shorthand_story_select' widget.
 *
 * @FieldWidget(
 *   id = "shorthand_story_select",
 *   label = @Translation("Shorthand Story select"),
 *   field_types = {
 *     "shorthand_story_id"
 *   }
 * )
 *
 * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
 *
 * @see https://www.drupal.org/project/shorthand/issues/3274487
 */
class StorySelectFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Valid Shorthand story ID pattern.
   */
  const STORY_ID_PATTERN = '[A-Za-z0-9_-]+';

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3274487
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3274487
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3274487
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->value ?? NULL,
      '#autocomplete_route_name' => 'shorthand.story_autocomplete',
      '#element_validate' => [
        [static::class, 'validateStoryValue'],
      ],
      '#description' => $this->t('Start typing to search Shorthand stories.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $autocomplete_id_pattern = '/\((' . self::STORY_ID_PATTERN . ')\)$/';
    foreach ($values as &$value) {
      $value['value'] = trim((string) ($value['value'] ?? ''));
      if (!empty($value['value']) && preg_match($autocomplete_id_pattern, $value['value'], $matches)) {
        $value['value'] = $matches[1];
      }
    }
    return $values;
  }

  /**
   * Validate the autocomplete value before it is saved.
   *
   * Selected autocomplete values are stored as "Title (story_id)"; existing
   * saved field values are stored as the raw story ID.
   */
  public static function validateStoryValue(array &$element, FormStateInterface $form_state, array &$form) {
    $value = trim((string) $element['#value']);
    if ($value === '') {
      return;
    }

    $autocomplete_id_pattern = '/\((' . self::STORY_ID_PATTERN . ')\)$/';
    $raw_id_pattern = '/^' . self::STORY_ID_PATTERN . '$/';
    if (preg_match($autocomplete_id_pattern, $value) || preg_match($raw_id_pattern, $value)) {
      return;
    }

    $form_state->setError($element, \Drupal::translation()->translate('Select a Shorthand story from the autocomplete suggestions, or enter a valid story ID.'));
  }

}
