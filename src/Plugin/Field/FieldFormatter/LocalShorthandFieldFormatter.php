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

    foreach ($items as $delta => $item) {
      // Render each element.
      $path = $item->value;
      $filePathHead = 'public://' . RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path . '/head.html';
      $filePathArticle = 'public://' . RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path . '/article.html';
      $public = PublicStream::basePath();

      $head = strip_tags(file_get_contents($filePathHead), '<link><style><script><title>');
      $html = $head . file_get_contents($filePathArticle);
      foreach (['assets', 'static'] as $folder) {
        $url = Url::fromUserInput('/' . $public . '/' . RemoteCollectionController::SHORTHAND_STORY_BASE_PATH . '/' . $path . '/' . $folder . '/', [
          'absolute' => TRUE,
        ])->toString();
        $html = str_replace('./' . $folder . '/', $url, $html);
      }

      // $value = $this->parseContent($html);
      $element[$delta] = [
        '#markup' => Markup::create($html),
        // '#allowed_tags' => ['article', 'div', 'p', 'h1', 'h2', 'h3', 'h4',
        // 'picture', 'img', 'canvas', 'source', 'video', 'figure'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function parseContent($html) {
    if ($html) {
      /*$html_dom = Html::load($html);

      // Title tag.
      $title_dom = $html_dom->getElementsByTagName('title')
      ->item(0)->firstChild;
      $title = $title_dom->ownerDocument->saveXML($title_dom);

      // Body tag.
      //    $body_dom = $html_dom->getElementsByTagName('body')->item(0);
      //    $body = str_replace('body>', 'div>', $body_dom->ownerDocument
      ->saveXML($body_dom));

      $body_dom = $html_dom->getElementsByTagName('article')->item(0);
      $body = $body_dom->ownerDocument->saveXML($body_dom);*/

    }
    else {
      $this->messenger()->addWarning($this->t('Local shorthand content is empty. It was either deleted or moved.'));
    }

    return $body;
  }

}
