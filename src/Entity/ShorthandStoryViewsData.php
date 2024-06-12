<?php

namespace Drupal\shorthand\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Shorthand story entities.
 *
 * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
 *
 * @see https://www.drupal.org/project/shorthand/issues/3274487
 */
class ShorthandStoryViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3274487
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    return $data;
  }

}
