<?php

namespace Drupal\shorthand;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\shorthand\Entity\ShorthandStoryInterface;

/**
 * Defines the storage handler class for Shorthand story entities.
 *
 * This extends the base storage class, adding required special handling for
 * Shorthand story entities.
 *
 * @ingroup shorthand
 */
interface ShorthandStoryStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Shorthand story revision IDs for a specific Shorthand story.
   *
   * @param \Drupal\shorthand\Entity\ShorthandStoryInterface $entity
   *   The Shorthand story entity.
   *
   * @return int[]
   *   Shorthand story revision IDs (in ascending order).
   */
  public function revisionIds(ShorthandStoryInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Shorthand story author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Shorthand story revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\shorthand\Entity\ShorthandStoryInterface $entity
   *   The Shorthand story entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ShorthandStoryInterface $entity);

  /**
   * Unsets the language for all Shorthand story with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
