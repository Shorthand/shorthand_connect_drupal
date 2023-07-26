<?php

namespace Drupal\shorthand\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\shorthand\Controller\RemoteCollectionController;
use Drupal\shorthand\ShorthandApiInterface;
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
   * @param \Drupal\shorthand\ShorthandApiInterface $shorthandApi
   *   The shorthand api connector.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ShorthandApiInterface $shorthandApi, RendererInterface $renderer, FileSystemInterface $file_system) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->shorthandApi = $shorthandApi;
    $this->fileSystem = $file_system;
    $this->shorthandStories = $this->shorthandApi->getStories();
    $this->renderer = $renderer;
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
      $container->get('shorthand_api'),
      $container->get('renderer'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'select',
      '#default_value' => $items[$delta]->value ?? NULL,
      '#options' => $this->buildStoriesList(),
      '#attached' => [
        'library' => [
          'shorthand/shorthandSelectionForm',
        ],
      ],
      '#suffix' => '<div id="shorthand-stories-data">' . json_encode($this->shorthandStories) . '</div>',
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
    $options = [0 => $this->t('- Select -')];

    $destination_uri = 'public://' . RemoteCollectionController::SHORTHAND_STORY_BASE_PATH;

    $storyFolders = $this->fileSystem->scanDirectory($destination_uri, '/.*/', [
      'recurse' => FALSE,
      'key' => 'filename',
    ]);

    $stories = [];
    try {
      $shorthandStories = $this->shorthandStories;
      if ($shorthandStories) {
        foreach ($shorthandStories as $story) {
          $stories[$story['id']] = [
            'title' => $story['title'],
            'versions' => [],
            'id' => $story['id'],
            'image' => $story['image'],
            'published' => $story['published'],
            'updated' => $story['updated'],
            'status' => $story['status'],
          ];
        }
      }
    }
    catch (ConnectException $error) {
    }

    

    foreach (array_keys($storyFolders) as $story_id) {
      $storyVersionFolders = $this->fileSystem->scanDirectory($destination_uri . '/' . $story_id, '/.*/', [
        'recurse' => FALSE,
        'key' => 'filename',
      ]);

      foreach (array_keys($storyVersionFolders) as $version_id) {
        $options[$story_id . '/' . $version_id] =
          $stories[$story_id]['title'] .' @ '. $version_id  ?? ($story_id . '/' . $version_id);
        array_push($stories[$story_id]['versions'], $version_id);
      }
    }

    $local_stories = array_filter($stories, function ($story) {
      return count($story['versions']) > 0;
    });

    if (empty($options)) {
      $options = [0 => 'No local stories found. Head to content > shorthand stories (remote).'];
    }

    $story_panels = [];

    foreach ($local_stories as $story) {
      $url = $story['image'];
      $title = $story['title'];
      $image_variables = [
        '#theme' => 'image',
        '#uri' => $url,
        '#alt' => $title,
        '#title' => $title,
        '#attributes' => [
          'class' => ['shorthand-story-image'],
        ]
      ];
      $story['image_tag'] = $this->renderer->render($image_variables);

      $story_panels[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['shorthand-story'],
          'data-storyid' => $story['id'],
          'data-storyoption' => $story['id']."/".$story['versions'][0],
          'data-storytitle' => $story['title'],
          'data-storystatus' => $story['status'],
        ],
        'content' => [
          'story_image' => [
            '#markup' => $story['image_tag'],
          ],
          'story_title' => [
            '#markup' => '<span>'.$story['title'].'</span>',
          ],
        ],
      ];
    }
    
    return $options;
  }

}
