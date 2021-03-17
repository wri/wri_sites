<?php

namespace Drupal\wri_author;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\wri_author\Entity\WRIAuthorInterface;

/**
 * Defines the storage handler class for Author entities.
 *
 * This extends the base storage class, adding required special handling for
 * Author entities.
 *
 * @ingroup wri_author
 */
interface WRIAuthorStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Author revision IDs for a specific Author.
   *
   * @param \Drupal\wri_author\Entity\WRIAuthorInterface $entity
   *   The Author entity.
   *
   * @return int[]
   *   Author revision IDs (in ascending order).
   */
  public function revisionIds(WRIAuthorInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Author author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Author revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\wri_author\Entity\WRIAuthorInterface $entity
   *   The Author entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(WRIAuthorInterface $entity);

  /**
   * Unsets the language for all Author with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
