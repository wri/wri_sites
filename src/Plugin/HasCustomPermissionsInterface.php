<?php

namespace Drupal\field_permissions\Plugin;

/**
 * Denotes that this field permission type generates custom permissions.
 */
interface HasCustomPermissionsInterface {

  /**
   * Returns an array of permissions suitable for use in a permission callback.
   *
   * @return array
   *   An array of permissions.
   */
  public function getPermissions();

}
