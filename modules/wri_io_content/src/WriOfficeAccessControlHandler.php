<?php

namespace Drupal\wri_io_content;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the WRI office entity.
 *
 * @see \Drupal\wri_io_content\Entity\WriOffice.
 */
class WriOfficeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\wri_io_content\Entity\WriOfficeInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished wri office entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published wri office entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit wri office entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete wri office entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add wri office entities');
  }

}
