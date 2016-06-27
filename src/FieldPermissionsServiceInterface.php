<?php

namespace Drupal\field_permissions;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implement FieldPermission Interface.
 */
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
  public static function listFieldPermissionSupport(FieldStorageConfigInterface $field, $label = '');

  /**
   * Get default value for checkbox role permission.
   */
  public static function getPermissionValue();

  /**
   * Returns permissions implements in field_permissions.
   */
  public static function permissions();

  /**
   * Get default value for checkbox  role permission.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field
   *   The field to return permissions for.
   */
  public static function fieldGetPermissionType(FieldStorageConfigInterface $field);

  /**
   * Get default value for checkbox  role permission.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field
   *   The field to return permissions for.
   * @param string $type_permission
   *   The field permission type.
   */
  public static function fieldSetPermissionType(FieldStorageConfigInterface $field, $type_permission);

  /**
   * Return permissions to grant access to admin field perm.
   *
   * @param AccountInterface $account
   *   User to grant permissions.
   *
   * @return bool
   *   Grant or negate access.
   */
  public static function getAccessAdminFieldPermissions(AccountInterface $account);

  /**
   * Return permissions to garenat access to private field.
   *
   * @param AccountInterface $account
   *   User to garant permissions.
   *
   * @return bool
   *   Garant or negate access.
   */
  public static function getAccessPrivateFieldPermissions(AccountInterface $account);

  /**
   * Field is attached to comment entity.
   *
   * @param FieldDefinitionInterface $field_definition
   *   Fields to get permissions.
   *
   * @return bool
   *   TRUE if in a comment entity.
   */
  public static function fieldIsCommentField(FieldDefinitionInterface $field_definition);

  /**
   * Get access for field by operations and account permisisons.
   *
   * @param string $operation
   *    String operation on field.
   * @param FieldItemListInterface $items
   *   The entity field object on which to check access.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param FieldDefinitionInterface $field_definition
   *   Fields to get permissions.
   */
  public static function getFieldAccess($operation, FieldItemListInterface $items, AccountInterface $account, FieldDefinitionInterface $field_definition);

  /**
   * Access to field on items and operations with FIELD_PERMISSIONS_PRIVATE.
   *
   * @param string $operation
   *    String operation on field.
   * @param EntityInterface $entity
   *   The entity field object on which to check access.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Field name to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessPrivate($operation, EntityInterface $entity, AccountInterface $account, $field_name);

  /**
   * Access to field on items VIEW and FIELD_PERMISSIONS_PRIVATE.
   *
   * @param EntityInterface $entity
   *   The entity field object on which to check access.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Field name to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessPrivateView(EntityInterface $entity, AccountInterface $account, $field_name);

  /**
   * Access to field on items EDIT and FIELD_PERMISSIONS_PRIVATE.
   *
   * @param EntityInterface $entity
   *   The entity field object on which to check access.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Field name to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessPrivateEdit(EntityInterface $entity, AccountInterface $account, $field_name);

  /**
   * Access to field on items and operations with FIELD_PERMISSIONS_CUSTOM.
   *
   * @param string $operation
   *    String operation on field.
   * @param EntityInterface $entity
   *   The entity field object on which to check access.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Field name to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessCustom($operation, EntityInterface $entity, AccountInterface $account, $field_name);

  /**
   * Access to field on items VIEW and FIELD_PERMISSIONS_CUSTOM.
   *
   * @param EntityInterface $entity
   *   The entity field object on which to check access.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Field name to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessCustomView(EntityInterface $entity, AccountInterface $account, $field_name);

  /**
   * Access to field on items EDIT and FIELD_PERMISSIONS_CUSTOM.
   *
   * @param EntityInterface $entity
   *   The entity field object on which to check access.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Field name to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessCustomEdit(EntityInterface $entity, AccountInterface $account, $field_name);

}
