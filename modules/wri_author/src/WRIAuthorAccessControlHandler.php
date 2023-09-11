<?php

namespace Drupal\wri_author;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Author entity.
 *
 * @see \Drupal\wri_author\Entity\WRIAuthor.
 */
class WRIAuthorAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\wri_author\Entity\WRIAuthorInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished author entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published author entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit author entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete author entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add author entities');
  }

}
