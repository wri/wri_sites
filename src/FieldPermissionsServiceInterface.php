<?php

/**
 * @file
 * Contains FieldPermissionsServiceInterface.php.
 */

namespace Drupal\field_permissions;


interface FieldPermissionsServiceInterface {

  /**
   * Obtain the list of field permissions.
   *
   * @param string $field_label
   *   The human readable name of the field to use when constructing permission
   *   names. Usually this will be derived from one or more of the field
   *   instance labels.
   */
  public static function getList($field_label = '');

  /**
   * Returns field permissions in format suitable for use in hook_permission.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field
   *   The field to return permissions for.
   *
   * @return array
   *   An array of permission information,
   */
  public static function listFieldPermissionSupport($field, $label = '');

  /**
   * Get default value for checkbox  role permission.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field
   *   The field to return permissions for.
   */
 public static function getPermissionValue(/*FieldStorageConfigInterface $field*/);

  /**
   * Returns permissions.
   */
  public static function permissions();

  /**
   * Get default value for checkbox  role permission.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field
   *   The field to return permissions for.
   */
  public static function fieldGetPermissionType($field);

  /**
   * {@inheritdoc}
   */
  public static function getFieldAccess($operation, $items, $account, $field_definition);

}
