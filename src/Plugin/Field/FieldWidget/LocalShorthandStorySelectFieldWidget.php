<?php

namespace Drupal\shorthand\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\shorthand\LocalStoryIndex;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'shorthand_local_story_select' widget.
 *
 * @FieldWidget(
 *   id = "shorthand_local_story_select",
 *   label = @Translation("Shorthand Story select"),
 *   field_types = {
 *     "shorthand_local"
 *   }
 * )
 */
class LocalShorthandStorySelectFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Valid Shorthand story ID pattern.
   */
  const STORY_ID_PATTERN = '[A-Za-z0-9_-]+';

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
   * The constructor method.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\shorthand\LocalStoryIndex $local_story_index
   *   The local story index.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, LocalStoryIndex $local_story_index, DateFormatterInterface $date_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->localStoryIndex = $local_story_index;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('shorthand.local_story_index'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $stored_value = (string) ($items[$delta]->value ?? '');
    $default_story_id = '';
    $default_version_id = '';
    if ($stored_value !== '' && str_contains($stored_value, '/')) {
      [$default_story_id, $default_version_id] = explode('/', $stored_value, 2);
    }

    // Prefer the story submitted in the current (AJAX-rebuilt) form state
    // over the stored field value. Values may not be populated yet during
    // the AJAX rebuild, so fall back to the raw user input.
    $selected_story_id = $default_story_id;
    $story_path = array_merge($element['#field_parents'], [$field_name, $delta, 'value', 'story']);
    $story_input = $form_state->getValue($story_path);
    if ($story_input === NULL) {
      $story_input = NestedArray::getValue($form_state->getUserInput(), $story_path);
    }
    if ($story_input !== NULL) {
      $selected_story_id = static::extractStoryId((string) $story_input);
    }

    $selected_story = $selected_story_id !== '' ? $this->localStoryIndex->get($selected_story_id) : NULL;

    $default_story_display = '';
    if ($default_story_id !== '') {
      $default_story = $this->localStoryIndex->get($default_story_id);
      $default_story_display = $default_story !== NULL
        ? $default_story['title'] . ' (' . $default_story_id . ')'
        : $default_story_id;
    }

    // Deterministic wrapper ID: Html::getUniqueId() appends a per-request
    // suffix, which would break replacement targeting after the first AJAX
    // rebuild.
    $version_wrapper_id = Html::cleanCssIdentifier($field_name . '-' . $delta . '-shorthand-version-wrapper');

    $element['value'] = $element + [
      '#type' => 'container',
      '#element_validate' => [
        [static::class, 'validateStorySelection'],
      ],
      '#attributes' => [
        'class' => ['shorthand-local-story-widget'],
        'data-shorthand-local-story-widget' => 'true',
        'data-shorthand-detail-url' => Url::fromRoute('shorthand.local_story_detail', ['story_id' => '_ID_'])->toString(),
        'data-shorthand-search-url' => Url::fromRoute('shorthand.local_story_autocomplete')->toString(),
        'data-shorthand-preview-url' => Url::fromRoute('shorthand.local_story_preview', [
          'story_id' => '_ID_',
          'version_id' => '_VERSION_',
        ])->toString(),
      ],
      '#attached' => [
        'library' => [
          'shorthand/shorthandSelectionForm',
        ],
      ],
    ];

    $element['value']['story'] = [
      '#type' => 'textfield',
      '#title' => $element['#title'] ?? $this->t('Shorthand story'),
      '#default_value' => $default_story_display,
      '#description' => empty($this->localStoryIndex->getAll())
        ? $this->t('No local stories found. Head to content > shorthand stories (remote).')
        : $this->t('Search downloaded Shorthand stories, or pick one from the list below.'),
      '#maxlength' => 1024,
      '#attributes' => [
        'data-shorthand-story-input' => 'true',
      ],
      // Custom event fired by shorthand-selection-form.js only when the
      // selected story actually changes, so interacting with the search
      // field without picking a story does not trigger a needless rebuild.
      '#ajax' => [
        'callback' => [static::class, 'updateVersionElement'],
        'event' => 'shorthandStoryChange',
        'wrapper' => $version_wrapper_id,
        'progress' => ['type' => 'throbber', 'message' => NULL],
      ],
    ];

    $element['value']['options'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['shorthand-story-options'],
        'data-shorthand-story-options' => 'true',
      ],
    ];

    $version_options = ['' => $this->t('Latest downloaded version')];
    if ($selected_story !== NULL) {
      foreach ($selected_story['versions'] as $version_id) {
        $version_options[$version_id] = $this->formatVersionLabel($version_id);
      }
    }

    // The wrapper div must always exist as the #ajax replacement target, so
    // hide it (rather than omit it) until a story is chosen.
    $version_wrapper_classes = $selected_story === NULL ? ' class="shorthand-version-hidden"' : '';
    $element['value']['version'] = [
      '#type' => 'select',
      '#title' => $this->t('Version'),
      '#options' => $version_options,
      '#default_value' => isset($version_options[$default_version_id]) ? $default_version_id : '',
      '#prefix' => '<div id="' . $version_wrapper_id . '"' . $version_wrapper_classes . '>',
      '#suffix' => '</div>',
    ];

    $element['value']['preview'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['shorthand-story-preview'],
        'data-shorthand-story-preview' => 'true',
      ],
    ];

    return $element;
  }

  /**
   * AJAX callback: refresh the version select for the chosen story.
   */
  public static function updateVersionElement(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    return $element['version'];
  }

  /**
   * Validate the selected story and version.
   */
  public static function validateStorySelection(array &$element, FormStateInterface $form_state, array &$form) {
    $story_raw = trim((string) $form_state->getValue(array_merge($element['#parents'], ['story'])));
    if ($story_raw === '') {
      return;
    }

    $story_id = static::extractStoryId($story_raw);
    $story = $story_id !== '' ? \Drupal::service('shorthand.local_story_index')->get($story_id) : NULL;

    if ($story === NULL) {
      $form_state->setError($element['story'], t('Select a downloaded Shorthand story from the autocomplete suggestions.'));
      return;
    }

    if (empty($story['versions'])) {
      $form_state->setError($element['story'], t('The selected Shorthand story has no downloaded versions.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $story_raw = trim((string) ($value['value']['story'] ?? ''));
      $version = trim((string) ($value['value']['version'] ?? ''));
      $value['value'] = '';

      if ($story_raw === '') {
        continue;
      }

      $story_id = static::extractStoryId($story_raw);
      $story = $story_id !== '' ? $this->localStoryIndex->get($story_id) : NULL;
      if ($story === NULL || empty($story['versions'])) {
        continue;
      }

      if ($version === '' || !in_array($version, $story['versions'], TRUE)) {
        $version = $story['versions'][0];
      }

      $value['value'] = $story_id . '/' . $version;
    }

    return $values;
  }

  /**
   * Extract a story ID from an autocomplete value or raw ID.
   *
   * Autocomplete selections are formatted as "Title (story_id)"; raw story
   * IDs are accepted as-is.
   *
   * @param string $value
   *   The submitted story value.
   *
   * @return string
   *   The story ID, or an empty string when the value is not recognised.
   */
  public static function extractStoryId($value) {
    $value = trim($value);
    if ($value === '') {
      return '';
    }

    if (preg_match('/\((' . self::STORY_ID_PATTERN . ')\)$/', $value, $matches)) {
      return $matches[1];
    }

    if (preg_match('/^' . self::STORY_ID_PATTERN . '$/', $value)) {
      return $value;
    }

    return '';
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
