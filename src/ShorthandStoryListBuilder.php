<?php

namespace Drupal\shorthand;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Shorthand story entities.
 *
 * @ingroup shorthand
 */
class ShorthandStoryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Shorthand story ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\shorthand\Entity\ShorthandStory $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.shorthand_story.edit_form',
      ['shorthand_story' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
