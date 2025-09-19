<?php

namespace Drupal\shorthand\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\UrlGeneratorInterface as FileUrlGeneratorInterface;
use Drupal\shorthand\Controller\RemoteCollectionController;
use Drupal\shorthand\ShorthandStreamWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'shorthand_local_story_render' formatter.
 */
#[FieldFormatter(
  id: "shorthand_local_story_render",
  label: new TranslatableMarkup("Shorthand Story render"),
  field_types: [
    "shorthand_local",
  ],
)]
class LocalShorthandFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * File URL generator.
   *
   * @var \Drupal\file\UrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Stream wrapper service.
   *
   * @var \Drupal\shorthand\ShorthandStreamWrapper
   */
  protected $streamWrapper;

  public function __construct($plugin_id, $plugin_definition, FileUrlGeneratorInterface $file_url_generator, ShorthandStreamWrapper $stream_wrapper) {
    parent::__construct($plugin_id, $plugin_definition);
    $this->fileUrlGenerator = $file_url_generator;
    $this->streamWrapper = $stream_wrapper;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $container->get('file_url_generator'),
      $container->get('shorthand.stream_wrapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays local shorthand story.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    // Render each element.
    foreach ($items as $delta => $item) {
      $path = $item->value;
      $file_path = $this->streamWrapper->getStorageUri(RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path);
      $file_path_theme = $file_path . '/theme.min.css';
      $file_path_head = $file_path . '/head.html';
      $file_path_article = $file_path . '/article.html';

      if (!file_exists($file_path_theme) || !file_exists($file_path_article)) {
        continue;
      }

      $story_base_uri = $this->streamWrapper->getStorageUri(RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path . '/');
      $assets_uri = $story_base_uri . 'assets/';
      $static_uri = $story_base_uri . 'static/';
      $assets_url = rtrim($this->fileUrlGenerator->generateAbsoluteString($assets_uri), '/');
      $static_url = rtrim($this->fileUrlGenerator->generateAbsoluteString($static_uri), '/');

      $html = file_get_contents($file_path_article);
      $head = file_get_contents($file_path_head);
      $story_base_url = rtrim($this->fileUrlGenerator->generateAbsoluteString($story_base_uri), '/');
      $html = str_replace('./assets/', $assets_url . '/', $html);
      $head = str_replace('./assets/', $assets_url . '/', $head);
      $html = str_replace('./static/', $static_url . '/', $html);
      $head = str_replace('./static/', $static_url . '/', $head);
      $html = str_replace('href="./', 'href="' . $story_base_url . '/', $html);
      $head = str_replace('href="./', 'href="' . $story_base_url . '/', $head);
      $html = str_replace('src="./', 'src="' . $story_base_url . '/', $html);
      $head = str_replace('src="./', 'src="' . $story_base_url . '/', $head);

      // Replace title.
      $head = preg_replace('#([<]title)(.*)([<]/title[>])#s', ' ', $head);

      $element[$delta] = [
        '#markup' => Markup::create($html),
        '#prefix' => Markup::create($head),
      ];
    }

    return $element;
  }

}
