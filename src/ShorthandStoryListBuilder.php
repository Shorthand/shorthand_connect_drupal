<?php

namespace Drupal\shorthand;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Shorthand story entities.
 *
 * @ingroup shorthand
 *
 * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
 */
class ShorthandStoryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Shorthand story ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\shorthand\Entity\ShorthandStory $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.shorthand_story.canonical',
      ['shorthand_story' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
