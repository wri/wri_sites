<?php

namespace Drupal\field_permissions;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implemenst FieldPermissionsServiceInterfaces.
 */
class FieldPermissionsService implements FieldPermissionsServiceInterface {

  /**
   * {@inheritdoc}
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
  public static function listFieldPermissionSupport(FieldStorageConfigInterface $field, $label = '') {
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
   * {@inheritdoc}
   */
  public static function permissions() {
    $permissions = [];
    /** @var FieldStorageConfigInterface[] $fields */
    $fields = \Drupal::entityTypeManager()->getStorage('field_storage_config')->loadMultiple();
    foreach ($fields as $key => $field) {
      $field_name = $field->getName();
      // Check if permissionType is not default, before creating permissions.
      $type = FieldPermissionsService::fieldGetPermissionType($field);
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
   * {@inheritdoc}
   */
  public static function fieldGetPermissionType(FieldStorageConfigInterface $field) {
    $config = \Drupal::service('config.factory')->getEditable('field_permissions.settings');
    $settings_name = 'type.field:' . $field->getName();
    $field_settings_perm = $config->get($settings_name);
    return ($field_settings_perm) ? $field_settings_perm : FIELD_PERMISSIONS_PUBLIC;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldSetPermissionType(FieldStorageConfigInterface $field, $type_permission) {
    $field_name = $field->getName();
    $config = \Drupal::service('config.factory')->getEditable('field_permissions.settings');
    $config->set('type.field:' . $field_name, $type_permission);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function getAccessAdminFieldPermissions(AccountInterface $account) {
    return $account->hasPermission("admin_field_permissions");
  }

  /**
   * {@inheritdoc}
   */
  public static function getAccessPrivateFieldPermissions(AccountInterface $account) {
    return $account->hasPermission("access_user_private_field");
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldIsCommentField(FieldDefinitionInterface $field_definition) {
    if (!\Drupal::hasService('comment.manager')) {
      // Comment module isn't enabled.
      return FALSE;
    }
    $field_name = $field_definition->getName();
    $field_names = \Drupal::service('comment.manager')->getFields($field_definition->getTargetEntityTypeId());
    // Comment field.
    if (in_array($field_name, array_keys($field_names))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFieldAccess($operation, FieldItemListInterface $items, AccountInterface $account, FieldDefinitionInterface $field_definition) {
    $default_type = FieldPermissionsService::fieldGetPermissionType($field_definition->getFieldStorageDefinition());
    if (in_array("administrator", $account->getRoles()) || $default_type == FIELD_PERMISSIONS_PUBLIC) {
      return TRUE;
    }
    // Field add to comment entity.
    if (FieldPermissionsService::fieldIsCommentField($field_definition)) {
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
   * {@inheritdoc}
   */
  public static function getFieldAccessPrivate($operation, EntityInterface $entity, AccountInterface $account, $field_name) {
    if (FieldPermissionsService::getAccessPrivateFieldPermissions($account)) {
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
   * {@inheritdoc}
   */
  public static function getFieldAccessCustom($operation, EntityInterface $entity, AccountInterface $account, $field_name) {
    if ($operation === "view") {
      return FieldPermissionsService::getFieldAccessCustomView($entity, $account, $field_name);
    }
    elseif ($operation === "edit") {
      return FieldPermissionsService::getFieldAccessCustomEdit($entity, $account, $field_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getFieldAccessPrivateView(EntityInterface $entity, AccountInterface $account, $field_name) {
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
   * {@inheritdoc}
   */
  public static function getFieldAccessPrivateEdit(EntityInterface $entity, AccountInterface $account, $field_name) {
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
   * {@inheritdoc}
   */
  public static function getFieldAccessCustomView(EntityInterface $entity, AccountInterface $account, $field_name) {
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
   * {@inheritdoc}
   */
  public static function getFieldAccessCustomEdit(EntityInterface $entity, AccountInterface $account, $field_name) {
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
