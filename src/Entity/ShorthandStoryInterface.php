<?php

namespace Drupal\shorthand\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Shorthand story entities.
 *
 * @ingroup shorthand
 *
 * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
 */
interface ShorthandStoryInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Get Shorthand Story ID.
   *
   * @return string
   *   The shorthand story ID.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function getShorthandStoryId();

  /**
   * Get Shorthand body.
   *
   * @return string
   *   Shorthand story body (component_article.html).
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function getBody();

  /**
   * Gets the Shorthand story name.
   *
   * @return string
   *   Name of the Shorthand story.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function getName();

  /**
   * Sets the Shorthand story name.
   *
   * @param string $name
   *   The Shorthand story name.
   *
   * @return \Drupal\shorthand\Entity\ShorthandStoryInterface
   *   The called Shorthand story entity.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function setName($name);

  /**
   * Gets the Shorthand story creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Shorthand story.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function getCreatedTime();

  /**
   * Sets the Shorthand story creation timestamp.
   *
   * @param int $timestamp
   *   The Shorthand story creation timestamp.
   *
   * @return \Drupal\shorthand\Entity\ShorthandStoryInterface
   *   The called Shorthand story entity.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Shorthand story published status indicator.
   *
   * Unpublished Shorthand story are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Shorthand story is published.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function isPublished();

  /**
   * Sets the published status of a Shorthand story.
   *
   * @param bool $published
   *   TRUE to set this Shorthand story to published, FALSE otherwise.
   *
   * @return \Drupal\shorthand\Entity\ShorthandStoryInterface
   *   The called Shorthand story entity.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function setPublished($published);

  /**
   * Gets the Shorthand story revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Shorthand story revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\shorthand\Entity\ShorthandStoryInterface
   *   The called Shorthand story entity.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Shorthand story revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function getRevisionUser();

  /**
   * Sets the Shorthand story revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\shorthand\Entity\ShorthandStoryInterface
   *   The called Shorthand story entity.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0.
   */
  public function setRevisionUserId($uid);

}
