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
 * Shorthand publishing configuration select widget plugin.
 *
 * @FieldWidget(
 *   id = "shorthand_publish_configuration_select",
 *   label = @Translation("Shorthand Publish Configuration select"),
 *   field_types = {
 *     "string"
 *   }
 * )
 *
 * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
 */
class PublishConfigurationSelectFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Shorthand Api service.
   *
   * @var \Drupal\shorthand\ShorthandApiInterface
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
    $this->shorthandPublishingConfigurations = $this->shorthandApi->getPublishingConfigurations();
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
      '#options' => $this->buildPublishingConfigurationList(),
    ];

    return $element;
  }

  /**
   * Return Shorthand Publishing Configurations.
   *
   * @return array
   *   Shorthand publishing configuration, keyed by Publishing Configuration ID.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  protected function buildPublishingConfigurationList() {
    if (!empty($configs = $this->shorthandPublishingConfigurations)) {
      $list = [];
      foreach ($configs as $config) {
        $list[json_encode($config)] = $config['name'];
      }
    }
    else {
      $list = [0 => 'No Publish Configurations Available'];
    }

    return $list;
  }

}
