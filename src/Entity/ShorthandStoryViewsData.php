<?php

namespace Drupal\shorthand\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Shorthand story entities.
 */
class ShorthandStoryViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    return $data;
  }

}
