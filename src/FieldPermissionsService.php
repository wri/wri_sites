<?php

namespace Drupal\field_permissions;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\Validator\Constraints\File;
use Drupal\Core\Field;


class FieldPermissionsService {

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
   * Returns field permissions in format suitable for use in hook_permission.
   *
   * @param \Drupal\field\Entity\FieldStorageConfig $field
   *   The field to return permissions for.
   *
   * @return array
   *   An array of permission information,
   */
  public function ListFieldPermissionSupport(\Drupal\field\Entity\FieldStorageConfig $field, $label ='') {
    $permissions = array();
    $permission_list = FieldPermissions::field_permissions_list($label);
    foreach ($permission_list as $permission_type => $permission_info) {
      $permission =  str_replace(' ', '_' , $permission_type) . '_' .  $field->getName();
      $permissions[$permission] = array(
        'title' => $permission_info['title']->__toString(),
        'description' => $permission_info['label']->__toString(),
      );
    }
    return $permissions;
  }

  // Get default value for checkbox  role permission.
  public function GetPermissionValue(\Drupal\field\Entity\FieldStorageConfig $field){
    $roules = user_roles();
    $field_field_permissions = [];
    $ye = new FieldPermissions();
    $field_permission_perm =  $ye->permissions();
    $permissions =  user_role_permissions();
    foreach($roules as $rule_name => $roule) {
      $roule_perms = $roule->getPermissions();
      $field_field_permissions[$rule_name] = [];
      // For all element set admin permission.
      if(/*empty($roule_perms) && */ $roule->isAdmin()) {
        foreach(array_keys($field_permission_perm) as $perm_name) {
          $field_field_permissions[$rule_name][] = $perm_name;
        }
      }
      else {
        foreach($roule_perms as $key => $roule_perm) {
          if(in_array($roule_perm, array_keys($field_permission_perm))){
            $field_field_permissions[$rule_name][] =  $roule_perm;
          }
        }
      }
    }
    return $field_field_permissions;
  }


  public function permissions() {
    $permissions = [];
    $instances = \Drupal::entityTypeManager()->getStorage('field_storage_config')->loadMultiple();
    foreach ($instances as $key => $instance) {
      $field_name = $instance->getName();
      $permission_list = $this->getList($field_name);
      $perms_name = array_keys($permission_list);
      foreach ($perms_name as $perm_name) {
        $name = str_replace(' ', '_', $perm_name) . '_' . $field_name;
        $permissions[$name] = $permission_list[$perm_name];
      }
    }
    return $permissions;
  }

}
