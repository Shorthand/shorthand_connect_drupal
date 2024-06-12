<?php

namespace Drupal\shorthand\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
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

    // Render each element.
    foreach ($items as $delta => $item) {
      $path = $item->value;
      $filePath = 'public://' . RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path;
      $filePathTheme = $filePath . '/theme.min.css';
      $filePathHead = $filePath . '/head.html';
      $filePathArticle = $filePath . '/article.html';

      if (!file_exists($filePathTheme) || !file_exists($filePathArticle)) {
        continue;
      }

      $public = PublicStream::basePath();
      $html = file_get_contents($filePathArticle);
      $head = file_get_contents($filePathHead);
      foreach (['assets', 'static'] as $folder) {
        $url = Url::fromUserInput('/' . $public . '/' . RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path . '/' . $folder . '/', [
          'absolute' => TRUE,
        ])->toString();
        $html = str_replace('./' . $folder . '/', $url, $html);
        $head = str_replace('./' . $folder . '/', $url, $head);
      }

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
