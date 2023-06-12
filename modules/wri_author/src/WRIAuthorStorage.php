<?php

namespace Drupal\wri_author;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\wri_author\Entity\WRIAuthorInterface;

/**
 * Defines the storage handler class for Author entities.
 *
 * This extends the base storage class, adding required special handling for
 * Author entities.
 *
 * @ingroup wri_author
 */
class WRIAuthorStorage extends SqlContentEntityStorage implements WRIAuthorStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(WRIAuthorInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {wri_author_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {wri_author_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(WRIAuthorInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {wri_author_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('wri_author_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
