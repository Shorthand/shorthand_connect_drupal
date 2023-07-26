<?php

namespace Drupal\shorthand\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Shorthand story entities.
 *
 * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
 */
class ShorthandStoryViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    return $data;
  }

}
