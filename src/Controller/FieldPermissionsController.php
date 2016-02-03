<?php

/**
 * @file
 * Contains FieldPermissionsController.php.
 */

namespace Drupal\field_permissions\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_permissions\FieldPermissionsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FieldPermissionsController extends ControllerBase {

  protected $fieldPermissions;

  /**
   * Boh boh.
   *
   * @param FieldPermissionsService $field_permissions_service.
   */
  public function __construct(FieldPermissionsService $field_permissions_service) {
    $this->fieldPermissions = $field_permissions_service;
  }

  /**
   * {@inheritdoc}
   *
   * Uses late static binding to create an instance of this class with
   * injected dependencies.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('field_permissions.permissions_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function content() {
    $build['table'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => $this->buildRows(),
    );
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $headers = array(
      t('Field name'),
      t('Field type'),
      t('Entity type'),
      t('Used in'),
    );
    $permissions_list = $this->fieldPermissions->getList();
    foreach ($permissions_list as $permission_type => $permission_info) {
      $headers[] = array('data' => $permission_info['label'], 'class' => 'field-permissions-header');
    }
    return $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return t('Field permissions');
  }

  /**
   * {@inheritdoc}
   */
  protected function buildRows() {
    $instances = \Drupal::entityTypeManager()->getStorage('field_storage_config')->loadMultiple();
    $rows = [];
    /*
     * @var $instance \Drupal\field\Entity\FieldStorageConfig
     */
    foreach ($instances as $key => $instance) {
      $rows[] = $this->buildRow($instance);
    }
    return $rows;
  }

  /**
   * XXXXX.
   * @param \Drupal\field\Entity\FieldStorageConfig $field_storage.
   * @return array
   *   xxx
   */
  protected function buildRow(FieldStorageConfig $field_storage) {
    $row = [];
    if ($field_storage->isLocked()) {
      $row[0]['class'] = array('menu-disabled');
      $row[0]['data']['id'] = $this->t('@field_name (Locked)', array('@field_name' => $field_storage->getName()));
    }
    else {
      $row[0]['data'] = $field_storage->getName();
    }
    $row[1]['data'] = $field_storage->getType();
    $row[2]['data'] = $field_storage->getTargetEntityTypeId();
    $row[3]['data'] = implode(",", $field_storage->getBundles());

    $default_type = $this->fieldPermissions->fieldGetPermissionType($field_storage);
    if ($default_type == FIELD_PERMISSIONS_PUBLIC) {
      $row[4]['data'] = t("Public field (author and administrators can edit, everyone can view)");
      $row[4]['colspan'] = 5;
    }
    elseif ($default_type == FIELD_PERMISSIONS_PRIVATE) {
      $row[4]['data'] = t("Private field (only author and administrators can edit and view)");
      $row[4]['colspan'] = 5;
    }
    elseif ($default_type == FIELD_PERMISSIONS_CUSTOM) {
      $row[4]['data'] = t("Custom field Permission ()");
      $row[4]['colspan'] = 5;
    }
    return $row;
  }

}
