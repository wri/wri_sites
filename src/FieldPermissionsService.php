<?php

namespace Drupal\field_permissions;

/**
 * Implemenst FieldPermissionsServiceInterfaces.
 */
class FieldPermissionsService implements FieldPermissionsServiceInterface {

  /**
   * Obtain the list of field permissions.
   *
   * @param string $field_label
   *   The human readable name of the field to use when constructing permission
   *   names. Usually this will be derived from one or more of the field
   *   instance labels.
   */
  public static function getList($field_label = '') {
    return array(
      'create' => array(
        'label' => t('Create field'),
        'title' => t('Create own value for field @field', array('@field' => $field_label)),
      ),
      'edit own' => array(
        'label' => t('Edit own field'),
        'title' => t('Edit own value for field @field', array('@field' => $field_label)),
      ),
      'edit' => array(
        'label' => t('Edit field'),
        'title' => t("Edit anyone's value for field @field", array('@field' => $field_label)),
      ),
      'view own' => array(
        'label' => t('View own field'),
        'title' => t('View own value for field @field', array('@field' => $field_label)),
      ),
      'view' => array(
        'label' => t('View field'),
        'title' => t("View anyone's value for field @field", array('@field' => $field_label)),
      ),
    );
  }

  /**
   * GetPermissions width title and description key of array the name of perm.
   *
   * @param FieldStorageConfigInterface $field
   *   The field to return permissions for.
   * @param string $label
   *   Param to pass FieldPermissionsService::getList($label).
   *
   * @see FieldPermissionsService::getList($label)
   *
   * @return array
   *   Return arry for permission ti singol field.
   */
  public static function listFieldPermissionSupport($field, $label = '') {
    $permissions = array();
    $permission_list = FieldPermissionsService::getList($label);
    foreach ($permission_list as $permission_type => $permission_info) {
      $permission = str_replace(' ', '_', $permission_type) . '_' . $field->getName();
      $permissions[$permission] = array(
        'title' => $permission_info['title']->__toString(),
        'description' => $permission_info['label']->__toString(),
      );
    }
    return $permissions;
  }

  /**
   * Get All permission Value for all field and all roles.
   */
  public static function getPermissionValue() {
    $roules = user_roles();
    $field_field_permissions = [];
    $field_permission_perm = FieldPermissionsService::permissions();
    $permissions = user_role_permissions(array());
    foreach ($roules as $rule_name => $roule) {
      $roule_perms = $roule->getPermissions();
      $field_field_permissions[$rule_name] = [];
      // For all element set admin permission.
      if ($roule->isAdmin()) {
        foreach (array_keys($field_permission_perm) as $perm_name) {
          $field_field_permissions[$rule_name][] = $perm_name;
        }
      }
      else {
        foreach ($roule_perms as $key => $roule_perm) {
          if (in_array($roule_perm, array_keys($field_permission_perm))) {
            $field_field_permissions[$rule_name][] = $roule_perm;
          }
        }
      }
    }
    return $field_field_permissions;
  }

  /**
   * Get implemets permissions invoke in field_permissions.permissions.yml.
   *
   * @return array
   *   Add custom permissions.
   */
  public static function permissions() {
    $permissions = [];
    $instances = \Drupal::entityTypeManager()->getStorage('field_storage_config')->loadMultiple();
    foreach ($instances as $key => $instance) {
      $field_name = $instance->getName();
      // Check if permissionType is not default, before creating permissions.
      $type = FieldPermissionsService::fieldGetPermissionType($instance);
      if ($type <> FIELD_PERMISSIONS_PUBLIC) {
        $permission_list = FieldPermissionsService::getList($field_name);
        $perms_name = array_keys($permission_list);
        foreach ($perms_name as $perm_name) {
          $name = str_replace(' ', '_', $perm_name) . '_' . $field_name;
          $permissions[$name] = $permission_list[$perm_name];
        }
      }
    }
    return $permissions;
  }

  /**
   * Get permission type for single field.
   *
   * @param FieldStorageConfigInterface $field
   *    Field to get permissions.
   *
   * @return int
   *   return PUBLIC || PRIVATE || CUSTOM
   */
  public static function fieldGetPermissionType($field) {
    $config = \Drupal::service('config.factory')->getEditable('field_permissions.settings');
    $settings_name = 'type.field:' . $field->getName();
    $field_settings_perm = $config->get($settings_name);
    return ($field_settings_perm) ? $field_settings_perm : FIELD_PERMISSIONS_PUBLIC;
  }

  /**
   *
   */
  public static function fieldSetPermissionType($field, $type_permission) {
    $field_name = $field->getName();
    $config = \Drupal::service('config.factory')->getEditable('field_permissions.settings');
    $config->set('type.field:' . $field_name, $type_permission);
    $config->save();
  }

  /**
   * Return permissions to garenat access to admin field perm.
   *
   * @param AccountInterface $account
   *   User to garant permissions.
   *
   * @return bool
   *   Garant or negate access.
   */
  public static function GetAccessAdminFieldPermissions($account) {
    return $account->hasPermission("admin_field_permissions");
  }

  /**
   * Return permissions to garenat access to private field.
   *
   * @param AccountInterface $account
   *   User to garant permissions.
   *
   * @return bool
   *   Garant or negate access.
   */
  public static function GetAccessPrivateFieldPermissions($account) {
    return $account->hasPermission("access_user_private_field");
  }

  /**
   * Field is attached to comment entity.
   *
   * @param FieldDefinitionInterface $field_definition
   *   Fields to get permissions.
   *
   * @return bool
   *   TRUE if in a comment entity.
   */
  public static function FieldIsCommentField($field_definition) {
    $field_name = $field_definition->getName();
    $field_names = \Drupal::service('comment.manager')->getFields('node');
    // Comment field.
    if (in_array($field_name, array_keys($field_names))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Return access to field on itemes and opertations.
   *
   * @param string $operation
   *    String operation on field.
   * @param Entity $items
   *   Entity cotain fields.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param FieldDefinitionInterface $field_definition
   *   Fields to get permissions.
   */
  public static function getFieldAccess($operation, $items, $account, $field_definition) {
    $default_type = FieldPermissionsService::fieldGetPermissionType($field_definition);
    if (in_array("administrator", $account->getRoles()) || $default_type == FIELD_PERMISSIONS_PUBLIC) {
      return TRUE;
    }
    // Field add to comment entity.
    if (FieldPermissionsService::FieldIsCommentField($field_definition)) {
      return TRUE;
    }
    // NO impement metod getOwnerId || entity not user or field_collection.
    // field collection request https://www.drupal.org/node/2734551
    $list_entity = array('comment', 'user');
    $field_name = $field_definition->getName();
    $entity = $items->getEntity();
    if (!method_exists($items->getEntity(), 'getOwnerId') && !in_array($items->getEntity()->getEntityTypeId(), $list_entity)) {
      return TRUE;
    }
    if ($default_type == FIELD_PERMISSIONS_PRIVATE) {
      return FieldPermissionsService::getFieldAccessPrivate($operation, $entity, $account, $field_name);
    }
    if ($default_type == FIELD_PERMISSIONS_CUSTOM) {
      return FieldPermissionsService::getFieldAccessCustom($operation, $entity, $account, $field_name);
    }
  }

  /**
   * Access to field on itemes and opertations whith FIELD_PERMISSIONS_PRIVATE.
   *
   * @param string $operation
   *    String operation on field.
   * @param Entity $entity
   *   Entity cotain fields.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Fieldsname to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessPrivate($operation, $entity, $account, $field_name) {
    if (FieldPermissionsService::GetAccessPrivateFieldPermissions($account)) {
      return TRUE;
    }
    if ($operation === "view") {
      return FieldPermissionsService::getFieldAccessPrivateView($entity, $account, $field_name);
    }
    elseif ($operation === "edit") {
      return FieldPermissionsService::getFieldAccessPrivateEdit($entity, $account, $field_name);
    }
  }

  /**
   * Access to field on itemes and opertations whith FIELD_PERMISSIONS_CUSTOM.
   *
   * @param string $operation
   *    String operation on field.
   * @param Entity $entity
   *   Entity cotain fields.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Fieldsname to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessCustom($operation, $entity, $account, $field_name) {
    if ($operation === "view") {
      return FieldPermissionsService::getFieldAccessCustomView($entity, $account, $field_name);
    }
    elseif ($operation === "edit") {
      return FieldPermissionsService::getFieldAccessCustomEdit($entity, $account, $field_name);
    }
  }

  /**
   * Access to field on itemes VIEW and FIELD_PERMISSIONS_PRIVATE.
   *
   * @param Entity $entity
   *   Entity cotain fields.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Fieldsname to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessPrivateView($entity, $account, $field_name) {
    // USER.
    if ($entity->getEntityTypeId() == 'user') {
      return $entity->id() == $account->id();
    }
    elseif ($entity->getEntityTypeId() == 'comment') {
      return $entity->get('uid')->target_id == $account->id();
    }
    else {
      return $entity->getOwnerId() == $account->id();
    }
  }

  /**
   * Access to field on itemes EDIT and FIELD_PERMISSIONS_PRIVATE.
   *
   * @param Entity $entity
   *   Entity cotain fields.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Fieldsname to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessPrivateEdit($entity, $account, $field_name) {
    if ($entity->isNew()) {
      return TRUE;
    }
    // USER.
    if ($entity->getEntityTypeId() == 'user') {
      return ($entity->id() == $account->id());
    }
    // COMMENT.
    elseif ($entity->getEntityTypeId() == 'comment') {
      return $entity->get('uid')->target_id == $account->id();
    }
    else {
      return $entity->getOwnerId() == $account->id();
    }
  }

  /**
   * Access to field on itemes VIEW and FIELD_PERMISSIONS_CUSTOM.
   *
   * @param Entity $entity
   *   Entity cotain fields.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Fieldsname to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessCustomView($entity, $account, $field_name) {
    if ($account->hasPermission("view_" . $field_name)) {
      return $account->hasPermission("view_" . $field_name);
    }
    else {
      // USER.
      if ($entity->getEntityTypeId() == 'user') {
        return $entity->id() == $account->id() && $account->hasPermission("view_own_" . $field_name);
      }
      // COMMENT.
      elseif ($entity->getEntityTypeId() == 'comment') {
        return $entity->get('uid')->target_id == $account->id() && $account->hasPermission("view_own_" . $field_name);
      }
      else {
        return $entity->getOwnerId() == $account->id() && $account->hasPermission("view_own_" . $field_name);
      }
    }
  }

  /**
   * Access to field on itemes EDIT and FIELD_PERMISSIONS_CUSTOM.
   *
   * @param Entity $entity
   *   Entity cotain fields.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param string $field_name
   *   Fieldsname to get permissions.
   *
   * @return bool
   *   Check permission.
   */
  public static function getFieldAccessCustomEdit($entity, $account, $field_name) {
    if ($entity->isNew()) {
      return $account->hasPermission("create_" . $field_name);
    }
    if ($account->hasPermission("edit_" . $field_name)) {
      return $account->hasPermission("edit_" . $field_name);
    }
    else {
      // USER.
      if ($entity->getEntityTypeId() == 'user') {
        return $entity->id() == $account->id() && $account->hasPermission("edit_own_" . $field_name);
      }
      // COMMENT.
      elseif ($entity->getEntityTypeId() == 'comment') {
        return $entity->get('uid')->target_id == $account->id() && $account->hasPermission("edit_own_" . $field_name);
      }
      else {
        return $entity->getOwnerId() == $account->id() && $account->hasPermission("edit_own_" . $field_name);
      }
    }
  }

}
