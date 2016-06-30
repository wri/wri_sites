<?php

namespace Drupal\field_permissions;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The field permission service.
 */
class FieldPermissionsService implements FieldPermissionsServiceInterface, ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct the field permission service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getList($field_label = '') {
    return [
      'create' => [
        'label' => $this->t('Create field'),
        'title' => $this->t('Create own value for field @field', ['@field' => $field_label]),
      ],
      'edit own' => [
        'label' => $this->t('Edit own field'),
        'title' => $this->t('Edit own value for field @field', ['@field' => $field_label]),
      ],
      'edit' => [
        'label' => $this->t('Edit field'),
        'title' => $this->t("Edit anyone's value for field @field", ['@field' => $field_label]),
      ],
      'view own' => [
        'label' => $this->t('View own field'),
        'title' => $this->t('View own value for field @field', ['@field' => $field_label]),
      ],
      'view' => [
        'label' => $this->t('View field'),
        'title' => $this->t("View anyone's value for field @field", ['@field' => $field_label]),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionList(FieldStorageConfigInterface $field) {
    $permissions = [];
    $permission_list = $this->getList($field->getName());
    foreach ($permission_list as $permission_type => $permission_info) {
      $permission = str_replace(' ', '_', $permission_type) . '_' . $field->getName();
      $permissions[$permission] = [
        'title' => $permission_info['title'],
        'description' => $permission_info['label'],
      ];
    }
    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionsByRole() {
    /** @var \Drupal\user\RoleInterface[] $roles */
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $field_field_permissions = [];
    $field_permission_perm = $this->getAllPermissions();
    foreach ($roles as $role_name => $role) {
      $role_permissions = $role->getPermissions();
      $field_field_permissions[$role_name] = [];
      // For all element set admin permission.
      if ($role->isAdmin()) {
        foreach (array_keys($field_permission_perm) as $perm_name) {
          $field_field_permissions[$role_name][] = $perm_name;
        }
      }
      else {
        foreach ($role_permissions as $key => $role_permission) {
          if (in_array($role_permission, array_keys($field_permission_perm))) {
            $field_field_permissions[$role_name][] = $role_permission;
          }
        }
      }
    }
    return $field_field_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllPermissions() {
    $permissions = [];
    /** @var FieldStorageConfigInterface[] $fields */
    $fields = $this->entityTypeManager->getStorage('field_storage_config')->loadMultiple();
    foreach ($fields as $key => $field) {
      $field_name = $field->getName();
      // Check if permissionType is not default, before creating permissions.
      $type = static::fieldGetPermissionType($field);
      if ($type <> FIELD_PERMISSIONS_PUBLIC) {
        $permission_list = $this->getList($field_name);
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
  public function fieldGetPermissionType(FieldStorageConfigInterface $field) {
    return $field->getThirdPartySetting('field_permissions', 'permission_type', FIELD_PERMISSIONS_PUBLIC);
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
  public function getFieldAccess($operation, FieldItemListInterface $items, AccountInterface $account, FieldDefinitionInterface $field_definition) {
    $default_type = $this->fieldGetPermissionType($field_definition->getFieldStorageDefinition());
    if (in_array("administrator", $account->getRoles()) || $default_type == FIELD_PERMISSIONS_PUBLIC) {
      return TRUE;
    }
    // Field add to comment entity.
    if (static::fieldIsCommentField($field_definition)) {
      return TRUE;
    }
    // Special handling for the user entity is supported. Otherwise, entities
    // must implement EntityOwnerInterface.
    // @todo Field collection request https://www.drupal.org/node/2734551
    $list_entity = ['user'];
    $field_name = $field_definition->getName();
    $entity = $items->getEntity();
    if (!($entity instanceof EntityOwnerInterface) && !in_array($entity->getEntityTypeId(), $list_entity)) {
      return TRUE;
    }
    if ($default_type == FIELD_PERMISSIONS_PRIVATE) {
      return $this->getFieldAccessPrivate($operation, $entity, $account, $field_name);
    }
    if ($default_type == FIELD_PERMISSIONS_CUSTOM) {
      return $this->getFieldAccessCustom($operation, $entity, $account, $field_name);
    }
  }

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
  protected function getFieldAccessPrivate($operation, EntityInterface $entity, AccountInterface $account, $field_name) {
    if ($account->hasPermission('access_user_private_field')) {
      return TRUE;
    }
    if ($operation === 'view') {
      return static::getFieldAccessPrivateView($entity, $account, $field_name);
    }
    elseif ($operation === 'edit') {
      return static::getFieldAccessPrivateEdit($entity, $account, $field_name);
    }
  }

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
  protected function getFieldAccessCustom($operation, EntityInterface $entity, AccountInterface $account, $field_name) {
    if ($operation === 'view') {
      return static::getFieldAccessCustomView($entity, $account, $field_name);
    }
    elseif ($operation === 'edit') {
      return static::getFieldAccessCustomEdit($entity, $account, $field_name);
    }
  }

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
  protected function getFieldAccessPrivateView(EntityInterface $entity, AccountInterface $account, $field_name) {
    // User entities don't implement `EntityOwnerInterface`.
    if ($entity->getEntityTypeId() == 'user') {
      return $entity->id() == $account->id();
    }
    else {
      return $entity->getOwnerId() == $account->id();
    }
  }

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
  protected function getFieldAccessPrivateEdit(EntityInterface $entity, AccountInterface $account, $field_name) {
    if ($entity->isNew()) {
      return TRUE;
    }
    // User entities don't implement `EntityOwnerInterface`.
    if ($entity->getEntityTypeId() == 'user') {
      return ($entity->id() == $account->id());
    }
    else {
      return $entity->getOwnerId() == $account->id();
    }
  }

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
  protected function getFieldAccessCustomView(EntityInterface $entity, AccountInterface $account, $field_name) {
    if ($account->hasPermission("view_" . $field_name)) {
      return $account->hasPermission("view_" . $field_name);
    }
    else {
      // User entities don't implement `EntityOwnerInterface`.
      if ($entity->getEntityTypeId() == 'user') {
        return $entity->id() == $account->id() && $account->hasPermission("view_own_" . $field_name);
      }
      else {
        return $entity->getOwnerId() == $account->id() && $account->hasPermission("view_own_" . $field_name);
      }
    }
  }

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
  protected function getFieldAccessCustomEdit(EntityInterface $entity, AccountInterface $account, $field_name) {
    if ($entity->isNew()) {
      return $account->hasPermission("create_" . $field_name);
    }
    if ($account->hasPermission("edit_" . $field_name)) {
      return $account->hasPermission("edit_" . $field_name);
    }
    else {
      // User entities don't implement `EntityOwnerInterface`.
      if ($entity->getEntityTypeId() == 'user') {
        return $entity->id() == $account->id() && $account->hasPermission("edit_own_" . $field_name);
      }
      else {
        return $entity->getOwnerId() == $account->id() && $account->hasPermission("edit_own_" . $field_name);
      }
    }
  }

}
