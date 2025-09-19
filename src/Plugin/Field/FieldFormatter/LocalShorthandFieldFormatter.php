<?php

namespace Drupal\shorthand\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Render\Markup;
use Drupal\shorthand\Controller\RemoteCollectionController;

/**
 * Plugin implementation of the 'shorthand_local_story_render' formatter.
 *
 * @FieldFormatter(
 *   id = "shorthand_local_story_render",
 *   label = @Translation("Shorthand Story render"),
 *   field_types = {
 *     "shorthand_local"
 *   }
 * )
 */
class LocalShorthandFieldFormatter extends FormatterBase {

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
    
    // Get the stream wrapper service.
    $stream_wrapper = \Drupal::service('shorthand.stream_wrapper');

    // Render each element.
    foreach ($items as $delta => $item) {
      $path = $item->value;
      $filePath = $stream_wrapper->getStorageUri(RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path);
      $filePathTheme = $filePath . '/theme.min.css';
      $filePathHead = $filePath . '/head.html';
      $filePathArticle = $filePath . '/article.html';

      if (!file_exists($filePathTheme) || !file_exists($filePathArticle)) {
        continue;
      }

      // Build absolute URLs for assets and static from the configured scheme.
      $file_url_generator = \Drupal::service('file_url_generator');
      $story_base_uri = $stream_wrapper->getStorageUri(RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path . '/');
      $assets_uri = $story_base_uri . 'assets/';
      $static_uri = $story_base_uri . 'static/';
      $assets_url = $file_url_generator->generateAbsoluteString($assets_uri);
      $static_url = $file_url_generator->generateAbsoluteString($static_uri);

      $html = file_get_contents($filePathArticle);
      $head = file_get_contents($filePathHead);
      $story_base_url = rtrim($file_url_generator->generateAbsoluteString($story_base_uri), '/');
      $html = str_replace('./assets/', rtrim($assets_url, '/') . '/', $html);
      $head = str_replace('./assets/', rtrim($assets_url, '/') . '/', $head);
      $html = str_replace('./static/', rtrim($static_url, '/') . '/', $html);
      $head = str_replace('./static/', rtrim($static_url, '/') . '/', $head);
      // Fallback for other relative resources like theme CSS and root files.
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
