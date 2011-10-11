<?php

/**
 * @file
 * Implementation of helper functions related to hook_field_access().
 */

/**
 * Implementation of hook_field_access('view').
 */
function _field_permissions_field_view_access($field_name, $obj_type, $object, $account) {
  // Check if user has access to view this field in any object.
  if (user_access('view ' . $field_name, $account)) {
    return TRUE;
  }

  // If the user has permission to view objects that they own, return TRUE if
  // they own this object or FALSE if they don't.
  if (user_access('view own ' . $field_name, $account)) {

    // When field_access('view') is invoked, it may or may not provide
    // an object. It will, almost always, except when this function is
    // invoked as a field access callback from Views, where it is used to
    // evaluate if the field can be included in the query itself. In this
    // case we should grant access. Views will invoke field_access('view')
    // again, indirectly, when rendering the fields using field_view_field(),
    // and at that point the object will be passed along, so we can do the
    // proper check for object ownership then.
    if (!isset($object)) {
      return TRUE;
    }

    return _field_permissions_object_is_owned_by_account($object, $account);
  }

  return FALSE;
}

/**
 * Implementation of hook_field_access('edit').
 */
function _field_permissions_field_edit_access($field_name, $obj_type, $object, $account) {
  // Check if user has access to edit this field on object creation.
  if (empty($object->nid)) {
    return user_access('create ' . $field_name, $account);
  }

  // Check if user has access to edit this field in any object.
  if (user_access('edit ' . $field_name, $account)) {
    return TRUE;
  }

  // If the user has permission to edit objects that they own, check if they
  // own this object and return TRUE if they do.
  if (user_access('edit own ' . $field_name, $account) && _field_permissions_object_is_owned_by_account($object, $account)) {
    return TRUE;
  }

  return FALSE;
}

/**
 * Returns TRUE if an object is owned by a user account, FALSE otherwise.
 */
function _field_permissions_object_is_owned_by_account($object, $account) {
  // Try to get the uid of the object owner from the object itself. If it's not
  // set (for example, if the object type does not store a uid or does not have
  // a concept of "ownership"), we need to assume that the provided user
  // account does not own it.
  return isset($object->uid) && $object->uid == $account->uid;
}
