<?php

namespace Drupal\shorthand\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\shorthand\Controller\RemoteCollectionController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'shorthand_local_story_select' widget.
 */
#[FieldWidget(
  id: "shorthand_local_story_select",
  label: new TranslatableMarkup("Shorthand Story select"),
  field_types: [
    "shorthand_local",
  ],
)]
class LocalShorthandStorySelectFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * List of locally downloaded stories.
   *
   * @var array
   */
  protected $shorthandStories = [];

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
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, FileSystemInterface $file_system, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->fileSystem = $file_system;
    $this->fileUrlGenerator = $file_url_generator;
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
      $container->get('file_system'),
      $container->get('file_url_generator')
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
      '#suffix' => '<div id="shorthand-stories-data">' . Html::escape(Json::encode($this->shorthandStories)) . '</div>',
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

    if (!$this->fileSystem->prepareDirectory($destination_uri, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->messenger()->addWarning($this->t('Error accessing shorthand stories folder.'));

      return $options;
    }

    $storyFolders = $this->fileSystem->scanDirectory($destination_uri, '/.*/', [
      'recurse' => FALSE,
      'key' => 'filename',
    ]);

    $stories = [];
    foreach (array_keys($storyFolders) as $story_id) {
      $storyVersionFolders = $this->fileSystem->scanDirectory($destination_uri . '/' . $story_id, '/.*/', [
        'recurse' => FALSE,
        'key' => 'filename',
      ]);

      foreach (array_keys($storyVersionFolders) as $version_id) {
        $story_version_uri = $destination_uri . '/' . $story_id . '/' . $version_id;
        if (!isset($stories[$story_id])) {
          $stories[$story_id] = $this->buildLocalStoryData($story_id, $version_id, $story_version_uri);
        }
        $options[$story_id . '/' . $version_id] = $stories[$story_id]['title'] . ' @ ' . $version_id;
      }
    }

    $this->shorthandStories = array_values($stories);

    if (count($options) === 1) {
      $options = [0 => 'No local stories found. Head to content > shorthand stories (remote).'];
    }

    return $options;
  }

  /**
   * Build story metadata from a downloaded local story version.
   *
   * @param string $story_id
   *   The Shorthand story ID.
   * @param string $version_id
   *   The local story version ID.
   * @param string $story_version_uri
   *   The local story version URI.
   *
   * @return array
   *   Story data shaped for shorthand-selection-form.js.
   */
  protected function buildLocalStoryData($story_id, $version_id, $story_version_uri) {
    $metadata = $this->extractHeadMetadata($story_version_uri . '/head.html');
    return [
      'id' => $story_id,
      'title' => $metadata['title'] ?: $story_id,
      'image' => $this->resolveLocalImageUrl($metadata['image'], $story_version_uri),
      'thumbnail_route' => Url::fromRoute('shorthand.local_thumbnail', [
        'story_id' => $story_id,
        'version_id' => $version_id,
      ])->toString(),
      'status' => $this->t('Downloaded')->render(),
      'metadata' => [
        'description' => $metadata['description'],
      ],
    ];
  }

  /**
   * Extract story metadata from a downloaded head.html file.
   *
   * @param string $head_uri
   *   The local head.html URI.
   *
   * @return array
   *   Extracted metadata.
   */
  protected function extractHeadMetadata($head_uri) {
    $metadata = [
      'title' => '',
      'description' => '',
      'image' => '',
    ];

    if (!file_exists($head_uri)) {
      return $metadata;
    }

    $head = file_get_contents($head_uri);
    $meta = [];
    if (preg_match_all('/<meta\s+[^>]*>/i', $head, $tags)) {
      foreach ($tags[0] as $tag) {
        $attributes = $this->parseHtmlAttributes($tag);
        $name = strtolower($attributes['property'] ?? $attributes['name'] ?? '');
        if ($name !== '' && isset($attributes['content'])) {
          $meta[$name] = $attributes['content'];
        }
      }
    }

    $metadata['title'] = $meta['og:title'] ?? $meta['twitter:title'] ?? '';
    $metadata['description'] = $meta['og:description'] ?? $meta['description'] ?? $meta['twitter:description'] ?? '';
    $metadata['image'] = $meta['og:image'] ?? $meta['twitter:image'] ?? '';

    if ($metadata['title'] === '' && preg_match('/<title[^>]*>(.*?)<\/title>/is', $head, $matches)) {
      $metadata['title'] = html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5);
    }

    return $metadata;
  }

  /**
   * Parse simple HTML tag attributes.
   *
   * @param string $tag
   *   The HTML tag.
   *
   * @return array
   *   Attribute values keyed by lowercase attribute name.
   */
  protected function parseHtmlAttributes($tag) {
    $attributes = [];
    if (preg_match_all('/([a-zA-Z_:.-]+)\s*=\s*(["\'])(.*?)\2/s', $tag, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $attributes[strtolower($match[1])] = html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5);
      }
    }
    return $attributes;
  }

  /**
   * Resolve a metadata image URL for use in the local selector.
   *
   * @param string $image
   *   The image URL extracted from metadata.
   * @param string $story_version_uri
   *   The local story version URI.
   *
   * @return string
   *   A URL suitable for an image src.
   */
  protected function resolveLocalImageUrl($image, $story_version_uri) {
    if ($image === '') {
      return '';
    }

    $relative_path = '';
    if (preg_match('/^https?:\/\//i', $image)) {
      $path = parse_url($image, PHP_URL_PATH) ?: '';
      $assets_position = strpos($path, '/assets/');
      if ($assets_position !== FALSE) {
        $relative_path = substr($path, $assets_position + 1);
      }
    }
    else {
      $relative_path = preg_replace('#^\./#', '', $image);
    }

    if ($relative_path !== '') {
      $relative_path = rawurldecode($relative_path);
      $local_uri = $story_version_uri . '/' . $relative_path;
      if (file_exists($local_uri)) {
        return $this->fileUrlGenerator->generateString($local_uri);
      }
    }

    return $image;
  }

}
