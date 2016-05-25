<?php

/**
 * @file
 * Contains FieldPermissionsService.php.
 */

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
    $field_settings_perm = $config->get('permission_type_' . $field->getName());
    return ($field_settings_perm) ? $field_settings_perm : FIELD_PERMISSIONS_PUBLIC;
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

    $field_name = $field_definition->getName();
    $field_names = \Drupal::service('comment.manager')->getFields('node');
    $default_type = FieldPermissionsService::fieldGetPermissionType($field_definition);
    // Comment field.
    if (in_array($field_name, array_keys($field_names))) {
      // IN DEV.
      // Delegate access field comment type to entity_comment.
      // @see field_permissions_entity_access($entity, $operation, $account)
      return TRUE;
    }
    // NO impement metod getOwnerId.
    if (!method_exists($items->getEntity(), 'getOwnerId') && $items->getEntity()->getEntityTypeId() != 'user') {
      return TRUE;
    }
    if (in_array("administrator", $account->getRoles()) || $default_type == FIELD_PERMISSIONS_PUBLIC) {
      return TRUE;
    }
    $field_name = $field_definition->getName();
    if ($default_type == FIELD_PERMISSIONS_PRIVATE) {
      if ($operation === "view") {
        // USER.
        if ($items->getEntity()->getEntityTypeId() == 'user') {
          if (FieldPermissionsService::GetAccessPrivateFieldPermissions($account)) {
            return TRUE;
          }
          if ($items->getEntity()->id() == $account->id()) {
            return $account->hasPermission($operation . "_own_" . $field_name);
          }
        }
        else {
          // NODE.
          if ($items->getEntity()->getOwnerId() == $account->id()) {
            return FieldPermissionsService::GetAccessPrivateFieldPermissions($account) || $account->hasPermission($operation . "_own_" . $field_name);
          }
          else {
            return FieldPermissionsService::GetAccessPrivateFieldPermissions($account);
          }
        }
      }
      elseif ($operation === "edit") {
        // USER.
        if ($items->getEntity()->getEntityTypeId() == 'user') {
          if ($items->getEntity()->id() == $account->id()) {
            return FieldPermissionsService::GetAccessPrivateFieldPermissions($account) || $account->hasPermission($operation . "_own_" . $field_name);
          }
        }
        else {
          if ($items->getEntity()->isNew()) {
            // Dev implement create permission.
            return TRUE;
          }
          elseif ($items->getEntity()->getOwnerId() == $account->id()) {
            return TRUE;
          }
          else {
            return FieldPermissionsService::GetAccessPrivateFieldPermissions($account);;
          }
        }
      }
    }
    if ($default_type == FIELD_PERMISSIONS_CUSTOM) {
      if ($operation === "view") {
        if ($account->hasPermission($operation . "_" . $field_name)) {
          return $account->hasPermission($operation . "_" . $field_name);
        }
        else {
          if (($items->getEntity()->getEntityTypeId() == 'user')) {
            if (($items->getEntity()->id() == $account->id())) {
              return $account->hasPermission($operation . "_own_" . $field_name);
            }
          }
          elseif ($items->getEntity()->getOwnerId() == $account->id()) {
            return $account->hasPermission($operation . "_own_" . $field_name);
          }
        }
      }
      elseif ($operation === "edit") {
        if ($items->getEntity()->isNew()) {
          return $account->hasPermission("create_" . $field_name);
        }
        if ($account->hasPermission($operation . "_" . $field_name)) {
          return $account->hasPermission($operation . "_" . $field_name);
        }
        else {
          if (($items->getEntity()->getEntityTypeId() == 'user')) {
            if (($items->getEntity()->id() == $account->id())) {
              return $account->hasPermission($operation . "_own_" . $field_name);
            }
          }
          elseif ($items->getEntity()->getOwnerId() == $account->id()) {
            return $account->hasPermission($operation . "_own_" . $field_name);
          }
        }
      }
    }
  }

}
