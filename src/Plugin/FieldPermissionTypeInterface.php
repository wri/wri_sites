<?php

namespace Drupal\field_permissions\Plugin;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleStorageInterface;

/**
 * A field permission type plugin interface.
 */
interface FieldPermissionTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * The permission type label.
   *
   * @return string
   */
  public function getLabel();

  /**
   * The permission type description.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Returns TRUE if access to the field is granted for a given account and
   * operation.
   *
   * @param string $operation
   *   The operation to check. Either 'view' or 'edit'.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the field is attached to.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check access for.
   *
   * @return bool
   *   The access result.
   */
  public function hasFieldAccess($operation, EntityInterface $entity, AccountInterface $account);

  /**
   * Build or alter the field admin form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The user role storage.
   */
  public function buildAdminForm(array &$form, FormStateInterface $form_state, RoleStorageInterface $role_storage);

  /**
   * Allows the plugin to react to the field settings form submission.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The user role storage.
   */
  public function submitAdminForm(array &$form, FormStateInterface $form_state, RoleStorageInterface $role_storage);

}
