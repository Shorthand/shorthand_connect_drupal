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
 *
 * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
 */
class StorySelectFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Shorthand Api service.
   *
   * @var \Drupal\shorthand\ShorthandApiInterface
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  protected $shorthandApi;

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ShorthandApiInterface $shorthandApi) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->shorthandApi = $shorthandApi;
    $this->shorthandStories = $this->shorthandApi->getStories();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('shorthand.api.v2')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'select',
      '#default_value' => $items[$delta]->value ?? NULL,
      '#options' => $this->buildStoriesList(),
      '#suffix' => '<div id="shorthand-stories-data">' . json_encode($this->shorthandStories) . '</div>',
    ];

    return $element;
  }

  /**
   * Return Shorthand stories.
   *
   * @return array
   *   Array of Shorthand stories, keyed by Story ID.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  protected function buildStoriesList() {
    if (($stories = $this->shorthandStories) !== FALSE) {
      $list = [];
      foreach ($stories as $story) {
        $list[$story['id']] = $story['title'];
      }
    }
    else {
      $list = [0 => 'Cannot retrieve stories'];
    }

    return $list;
  }

}
