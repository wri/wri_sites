<?php

/**
 * @file
 * Contains FieldPermissionsService.php.
 */

namespace Drupal\field_permissions;
use Drupal\field\FieldStorageConfigInterface;


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
   * {@inheritdoc}
   */
  public function listFieldPermissionSupport(FieldStorageConfigInterface $field, $label = '') {
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
   * {@inheritdoc}
   */
  public function getPermissionValue(FieldStorageConfigInterface $field) {
    $roules = user_roles();
    $field_field_permissions = [];
    $field_permission_perm = FieldPermissionsService::permissions();
    $permissions = user_role_permissions();
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
   * {@inheritdoc}
   */
  public function permissions() {
    $permissions = [];
    $instances = \Drupal::entityTypeManager()->getStorage('field_storage_config')->loadMultiple();
    foreach ($instances as $key => $instance) {
      $field_name = $instance->getName();
      $permission_list = FieldPermissionsService::getList($field_name);
      $perms_name = array_keys($permission_list);
      foreach ($perms_name as $perm_name) {
        $name = str_replace(' ', '_', $perm_name) . '_' . $field_name;
        $permissions[$name] = $permission_list[$perm_name];
      }
    }
    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldGetPermissionType(FieldStorageConfigInterface $field) {
    $config = \Drupal::service('config.factory')->getEditable('field_permissions.field.settings');
    $field_settings_perm = $config->get('permission_type_' . $field->getName());
    return ($field_settings_perm) ? $field_settings_perm : FIELD_PERMISSIONS_PUBLIC;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldAccess($operation, $items, AccountInterface $account, $field_definition) {

    $default_type = FieldPermissionsService::fieldGetPermissionType($field_definition);
    if (in_array("administrator", $account->getRoles()) || $default_type == FIELD_PERMISSIONS_PUBLIC) {
      return TRUE;
    }
    if ($default_type == FIELD_PERMISSIONS_PRIVATE) {
      if ($operation === "view") {
        if ($items->getEntity()->getOwnerId() == $account->id()) {
          return $account->hasPermission($operation . "_own_" . $field_name);
        }
        else {
          return FALSE;
        }
      }
      elseif ($operation === "edit") {
        if ($items->getEntity()->isNew()) {
          return $account->hasPermission("create_" . $field_name);
        }
        elseif ($items->getEntity()->getOwnerId() == $account->id()) {
          return $account->hasPermission($operation . "_own_" . $field_name);
        }
        else {
          return FALSE;
        }
      }
    }
    if ($default_type == FIELD_PERMISSIONS_CUSTOM) {
      if ($operation === "view") {
        if ($account->hasPermission($operation . "_" . $field_name)) {
          return $account->hasPermission($operation . "_" . $field_name);
        }
        elseif ($items->getEntity()->getOwnerId() == $account->id()) {
          return $account->hasPermission($operation . "_own_" . $field_name);
        }
      }
      elseif ($operation === "edit") {
        if ($items->getEntity()->isNew()) {
          return $account->hasPermission("create_" . $field_name);
        }
        if ($account->hasPermission($operation . "_" . $field_name)) {
          return $account->hasPermission($operation . "_" . $field_name);
        }
        elseif ($items->getEntity()->getOwnerId() == $account->id()) {
          return $account->hasPermission($operation . "_own_" . $field_name);
        }
      }
    }
  }

}
