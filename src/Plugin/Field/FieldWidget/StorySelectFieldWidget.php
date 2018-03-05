<?php

namespace Drupal\shorthand\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\shorthand\ShorthandApiInterface;
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
 */
class StorySelectFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Shorthand Api service.
   *
   * @var \Drupal\shorthand\ShorthandApiInterface
   */
  protected $shorthandApi;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ShorthandApiInterface $shorthandApi) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->shorthandApi = $shorthandApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $version = StorySelectFieldWidget::getShorthandAPIVersion();
    $apiservice = 'shorthand.api';
    if ($version == '2') {
      $apiservice = 'shorthand.api.v2';
    }
    return new static(
        $plugin_id,
        $plugin_definition,
        $configuration['field_definition'],
        $configuration['settings'],
        $configuration['third_party_settings'],
        $container->get($apiservice)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'select',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#options' => $this->buildStoriesList(),
    ];

    return $element;
  }

  /**
   * Return Shorthand stories.
   *
   * @return array
   *   Array of Shorthand stories, keyed by Story ID.
   */
  protected function buildStoriesList() {
    $stories = $this->shorthandApi->getStories();

    $list = [];
    foreach ($stories as $story) {
      $list[$story['id']] = $story['title'];
    }

    return $list;
  }

  /**
   * Get the API version of Shorthand
   * @return string
   *   The version of the configured Shorthand API
   */
  protected static function getShorthandAPIVersion() {
    return \Drupal::service('settings')->get('shorthand_version', '1');
  }

}
