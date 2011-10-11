<?php

/**
 * @file
 * Administrative interface for the Field Permissions module.
 */

/**
 * Obtain the list of field permissions.
 *
 * @param $field_label
 *   The human readable name of the field to use when constructing permission
 *   names. Usually this will be derived from one or more of the field instance
 *   labels.
 */
function field_permissions_list($field_label = '') {
  return array(
    'create' => array(
      'label' => t('Create field'),
      'title' => t('Create own value for field %field', array('%field' => $field_label)),
    ),
    'edit own' => array(
      'label' => t('Edit own field'),
      'title' => t('Edit own value for field %field', array('%field' => $field_label)),
    ),
    'edit' => array(
      'label' => t('Edit field'),
      'title' => t("Edit anyone's value for field %field", array('%field' => $field_label)),
    ),
    'view own' => array(
      'label' => t('View own field'),
      'title' => t('View own value for field %field', array('%field' => $field_label)),
    ),
    'view' => array(
      'label' => t('View field'),
      'title' => t("View anyone's value for field %field", array('%field' => $field_label)),
    ),
  );
}

/**
 * Returns field permissions in a format suitable for use in hook_permission().
 *
 * @param $field
 *   The field to return permissions for.
 * @param $label
 *   (optional) The human readable name of the field to use when constructing
 *   permission names; for example, this might be the label of one of the
 *   corresponding field instances. If not provided, an appropriate label will
 *   be automatically derived from all the field's instances.
 *
 * @return
 *   An array of permission information, suitable for use in hook_permission().
 */
function field_permissions_list_field_permissions($field, $label = NULL) {
  $description = '';

  // If there is no preferred label, construct one from all the instance
  // labels.
  if (!isset($label)) {
    $labels = array();
    foreach ($field['bundles'] as $entity_type => $bundles) {
      foreach ($bundles as $bundle_name) {
        $instance = field_info_instance($entity_type, $field['field_name'], $bundle_name);
        $labels[] = $instance['label'];
      }
    }
    // If all the instances have the same label, just use that. Otherwise, use
    // the field name (with the full list of instance labels as the permission
    // description).
    $labels = array_unique($labels);
    if (count($labels) == 1) {
      $label = array_shift($labels);
    }
    else {
      $label = $field['field_name'];
      $description = t('This field appears as: %instances', array('%instances' => implode(', ', $labels)));
    }
  }

  $permissions = array();
  foreach (field_permissions_list($label) as $permission_type => $permission_info) {
    $permission = $permission_type . ' ' . $field['field_name'];
    $permissions[$permission] = array(
      'title' => $permission_info['title'],
      'description' => $description,
    );
  }

  return $permissions;
}

/**
 * Implementation of hook_permission().
 */
function _field_permissions_permission() {
  $perms = array(
    'administer field permissions' => array(
      'title' => t('Administer field permissions'),
      'description' => t('Manage field permissions and field permissions settings.'),
      'restrict access' => TRUE,
    ),
    'access private fields' => array(
      'title' => t("Access other users' private fields"),
      'description' => t('View and edit the stored values of all private fields.'),
      'restrict access' => TRUE,
    ),
  );

  foreach (field_info_fields() as $field) {
    if (isset($field['field_permissions']['type']) && $field['field_permissions']['type'] == FIELD_PERMISSIONS_CUSTOM) {
      $perms += field_permissions_list_field_permissions($field);
    }
  }

  return $perms;
}

/**
 * Alter the field settings form.
 */
function _field_permissions_field_settings_form_alter(&$form, $form_state, $form_id) {
  // Put the field permissions extensions at the top of the field settings
  // fieldset.
  $form['field']['field_permissions'] = array(
    '#weight' => -10,
    '#access' => user_access('administer field permissions'),
  );

  $form['field']['field_permissions']['type'] = array(
    '#title' => t('Field visibility and permissions'),
    '#type' => 'radios',
    '#options' => array(
      FIELD_PERMISSIONS_PUBLIC => t('Public (author and administrators can edit, everyone can view)'),
      FIELD_PERMISSIONS_PRIVATE => t('Private (only author and administrators can edit and view)'),
      FIELD_PERMISSIONS_CUSTOM => t('Custom permissions'),
    ),
    '#default_value' => isset($form['#field']['field_permissions']['type']) ? $form['#field']['field_permissions']['type'] : FIELD_PERMISSIONS_PUBLIC,
  );

  // Add the container in which the field permissions matrix will be displayed.
  // (and make it so that it is only visible when custom permissions are being
  // used).
  $form['field']['field_permissions']['permissions'] = array(
    '#type' => 'container',
    '#states' => array(
      'visible' => array(
        // We must cast this to a string until http://drupal.org/node/879580 is
        // fixed.
        ':input[name="field[field_permissions][type]"]' => array('value' => (string) FIELD_PERMISSIONS_CUSTOM),
      ),
    ),
    // Custom styling for the permissions matrix on the field settings page.
    '#attached' => array(
      'css' => array(drupal_get_path('module', 'field_permissions') . '/css/field_permissions.field.settings.css'),
    ),
  );

  // Add the field permissions matrix itself. Wait until the #pre_render stage
  // to move it to the above container, to avoid having the permissions data
  // saved as part of the field record.
  $form['field_permissions']['#tree'] = TRUE;
  $form['field_permissions']['#access'] = user_access('administer field permissions');
  $form['field_permissions']['permissions'] = field_permissions_permissions_matrix($form['#field'], $form['#instance']);
  $form['#pre_render'][] = '_field_permissions_field_settings_form_pre_render';

  // Add a submit handler to process the field permissions settings. Note that
  // it is important for this to run *after* the main field UI submit handler
  // (which saves the field itself), since when a new field is being created,
  // our submit handler will try to assign any new custom permissions
  // immediately, and our hook_permission() implementation relies on the field
  // info being up-to-date in order for that to work correctly.
  $form['#submit'][] = '_field_permissions_field_settings_form_submit';
}

/**
 * Returns a field permissions matrix that can be inserted into a form.
 *
 * The matrix's display is based on that of Drupal's default permissions page.
 *
 * Note that this matrix must be accompanied by an appropriate submit handler
 * (attached to the top level of the form) in order for the permissions in it
 * to actually be saved. For an example submit handler, see
 * _field_permissions_field_settings_form_submit().
 *
 * @param $field
 *   The field whose permissions will be displayed in the matrix.
 * @param $instance
 *   The field instance for which the permissions will be displayed. Although
 *   the permissions are per-field rather than per-instance, the instance label
 *   will be used to display an appropriate human-readable name for each
 *   permission.
 *
 * @return
 *   A form array defining the permissions matrix.
 *
 * @see user_admin_permissions()
 * @see _field_permissions_field_settings_form_submit()
 */
function field_permissions_permissions_matrix($field, $instance) {
  // This function primarily contains a simplified version of the code from
  // user_admin_permissions().
  $form['#theme'] = 'user_admin_permissions';
  $options = array();
  $status = array();

  // Retrieve all role names for use in the submit handler.
  $role_names = user_roles();
  $form['role_names'] = array(
    '#type' => 'value',
    '#value' => $role_names,
  );

  // Retrieve the permissions for each role, and the field permissions we will
  // be assigning here.
  $role_permissions = user_role_permissions($role_names);
  $field_permissions = field_permissions_list_field_permissions($field, $instance['label']);

  // Determine if it is safe to reset the default values for this field's
  // permissions. If this is a new field (never saved with field permission
  // data before), or if it's an existing field that is not currently using
  // custom permissions and doesn't have any previously-saved ones already in
  // the database, then it will be safe to reset them.
  $reset_permissions_defaults = FALSE;
  if (!isset($field['field_permissions']['type'])) {
    $reset_permissions_defaults = TRUE;
  }
  elseif ($field['field_permissions']['type'] != FIELD_PERMISSIONS_CUSTOM) {
    $all_assigned_permissions = call_user_func_array('array_merge_recursive', $role_permissions);
    $assigned_field_permissions = array_intersect_key($all_assigned_permissions, $field_permissions);
    $reset_permissions_defaults = empty($assigned_field_permissions);
  }
  // Store this information on the form so that other modules can use it (for
  // example, if they want to set default permissions for other roles besides
  // the admin role which we use it for below).
  $form['#field_permissions_are_new'] = $reset_permissions_defaults;

  // Go through each field permission we will display.
  foreach ($field_permissions as $permission => $info) {
    // Display the name of the permission as a form item.
    $form['permission'][$permission] = array(
      '#type' => 'item',
      '#markup' => $info['title'],
    );
    // Save it to be displayed as one of the role checkboxes.
    $options[$permission] = '';
    // If we are in a situation where we can reset the field permissions
    // defaults, we do so by pre-checking the admin role's checkbox for this
    // permission.
    if ($reset_permissions_defaults) {
      if (($admin_rid = variable_get('user_admin_role', 0)) && isset($role_names[$admin_rid])) {
        $status[$admin_rid][] = $permission;
      }
      // For fields attached to users, we also pre-check the anonymous user's
      // checkbox for the permission to create the field, since that is the
      // most common way in which new user entities are created.
      if ($instance['entity_type'] == 'user' && $permission == 'create ' . $field['field_name']) {
        $status[DRUPAL_ANONYMOUS_RID][] = $permission;
      }
    }
    // Otherwise (e.g., for fields with custom permissions already saved),
    // determine whether the permission is already assigned and check each
    // checkbox accordingly.
    else {
      foreach ($role_names as $rid => $name) {
        if (isset($role_permissions[$rid][$permission])) {
          $status[$rid][] = $permission;
        }
      }
    }
  }

  // Build the checkboxes for each role.
  foreach ($role_names as $rid => $name) {
    $form['checkboxes'][$rid] = array(
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => isset($status[$rid]) ? $status[$rid] : array(),
      '#attributes' => array('class' => array('rid-' . $rid)),
    );
    $form['role_names'][$rid] = array('#markup' => check_plain($name), '#tree' => TRUE);
  }

  // Attach the default permissions page JavaScript.
  $form['#attached']['js'][] = drupal_get_path('module', 'user') . '/user.permissions.js';

  // Attach our custom JavaScript for the permission matrix.
  $form['#attached']['js'][] = drupal_get_path('module', 'field_permissions') . '/js/field_permissions.field.settings.js';

  return $form;
}

/**
 * Pre-render function for the permissions matrix on the field settings form.
 */
function _field_permissions_field_settings_form_pre_render($form) {
  // Move the permissions matrix to its final location.
  $form['field']['field_permissions']['permissions']['matrix'] = $form['field_permissions']['permissions'];
  unset($form['field_permissions']);
  return $form;
}

/**
 * Form callback; Submit handler for the Field Settings form.
 */
function _field_permissions_field_settings_form_submit($form, &$form_state) {
  // Save the field permissions when appropriate to do so.
  $new_field_permissions_type = $form_state['values']['field']['field_permissions']['type'];
  if ($new_field_permissions_type == FIELD_PERMISSIONS_CUSTOM && isset($form_state['values']['field_permissions']['permissions'])) {
    $field_permissions = $form_state['values']['field_permissions']['permissions'];
    foreach ($field_permissions['role_names'] as $rid => $name) {
      user_role_change_permissions($rid, $field_permissions['checkboxes'][$rid]);
    }
  }

  // We must clear the page and block caches whenever the field permission type
  // setting has changed (because users may now be allowed to see a different
  // set of fields). For similar reasons, we must clear these caches whenever
  // custom field permissions are being used, since those may have changed too;
  // see user_admin_permissions_submit().
  if (!isset($form['#field']['field_permissions']['type']) || $new_field_permissions_type != $form['#field']['field_permissions']['type'] || $new_field_permissions_type == FIELD_PERMISSIONS_CUSTOM) {
    cache_clear_all();
  }
}

/**
 * Menu callback; Field permissions overview.
 */
function field_permissions_overview() {
  drupal_add_css(drupal_get_path('module', 'field_permissions') .'/css/field_permissions.admin.css');

  $headers = array(t('Field name'), t('Field type'), t('Object type'), t('Used in'));
  foreach (field_permissions_list() as $permission_type => $permission_info) {
    $headers[] = array('data' => $permission_info['label'], 'class' => 'field-permissions-header');
  }
  $destination = drupal_get_destination();

  // Load list of field instances, types and bundles in the system.
  $instances = field_info_instances();
  $field_types = field_info_field_types();
  $bundles = field_info_bundles();

  // Retrieve the permissions for each role.
  $role_permissions = user_role_permissions(user_roles());

  // Based on field_ui_fields_list() in field_ui.admin.inc.
  $rows = array();
  foreach ($instances as $obj_type => $type_bundles) {
    foreach ($type_bundles as $bundle => $bundle_instances) {
      foreach ($bundle_instances as $field_name => $instance) {
        // Each field will have a row in the table.
        $field = field_info_field($field_name);
        $admin_path = _field_ui_bundle_admin_path($obj_type, $bundle);
        $rows[$field_name]['data'][0] = $field['locked'] ? t('@field_name (Locked)', array('@field_name' => $field_name)) : $field_name;
        $rows[$field_name]['data'][1] = t($field_types[$field['type']]['label']);
        $rows[$field_name]['data'][2] = $obj_type;
        $rows[$field_name]['data'][3][] = l($bundles[$obj_type][$bundle]['label'], $admin_path . '/fields/'. $field_name, array(
          'query' => $destination,
          'fragment' => 'edit-field-field-permissions-type',
        ));
        $rows[$field_name]['class'] = $field['locked'] ? array('menu-disabled') : array('');

        // Append field permissions information to the report.
        $type = isset($field['field_permissions']['type']) ? $field['field_permissions']['type'] : FIELD_PERMISSIONS_PUBLIC;
        foreach (array_keys(field_permissions_list_field_permissions($field)) as $index => $permission) {
          // Put together the data value for the cell.
          $data = '';
          $full_colspan = FALSE;
          if ($type == FIELD_PERMISSIONS_PUBLIC) {
            $data = t('Public field (author and administrators can edit, everyone can view)');
            $full_colspan = TRUE;
          }
          elseif ($type == FIELD_PERMISSIONS_PRIVATE) {
            $data = t('Private field (only author and administrators can edit and view)');
            $full_colspan = TRUE;
          }
          else {
            // This is a field with custom permissions. Link the field to the
            // appropriate row of the permissions page, and theme it based on
            // whether all users have access.
            $all_users_have_access = isset($role_permissions[DRUPAL_ANONYMOUS_RID][$permission]) && isset($role_permissions[DRUPAL_AUTHENTICATED_RID][$permission]);
            $status_class = $all_users_have_access ? 'field-permissions-status-on' : 'field-permissions-status-off';
            $title = $all_users_have_access ? t('All users have this permission') : t('Not all users have this permission');
            $data = l('', 'admin/people/permissions', array(
              'attributes' => array(
                'class' => array('field-permissions-status', $status_class),
                'title' => $title,
              ),
              'query' => $destination,
              'fragment' => drupal_html_class("edit $permission"),
            ));
          }

          // Construct the cell.
          $rows[$field_name]['data'][4 + $index] = array(
            'data' => $data,
            'class' => array('field-permissions-cell'),
          );
          if ($full_colspan) {
            $rows[$field_name]['data'][4 + $index]['colspan'] = 5;
            break;
          }
        }
      }
    }
  }
  foreach ($rows as $field_name => $cell) {
    $rows[$field_name]['data'][3] = implode(', ', $cell['data'][3]);
  }
  if (empty($rows)) {
    $output = t('No fields have been defined for any content type yet.');
  }
  else {
    // Sort rows by field name.
    ksort($rows);

    // Allow external modules alter the table headers and rows.
    foreach (module_implements('field_permissions_overview_alter') as $module) {
      $function = $module .'_field_permissions_overview_alter';
      $function($headers, $rows);
    }

    $output = theme('table', array('header' => $headers, 'rows' => $rows));
  }
  return $output;
}

/**
 * Menu callback; Field permissions autocomplete.
 */
function field_permissions_autocomplete($type = '', $string = '') {
  $matches = array();
  if (!empty($string)) {
    if ($type == 'nodes') {
      $result = db_query_range(db_rewrite_sql("SELECT n.nid, n.title FROM {node} n WHERE LOWER(n.title) LIKE LOWER('%%%s%%') ORDER BY n.title"), $string, 0, 10);
      while ($row = db_fetch_object($result)) {
        $matches[$row->title ." [nid:$row->nid]"] = '<div class="reference-autocomplete">'. check_plain($row->title) . '</div>';
      }
    }
    elseif ($type == 'users') {
      $result = db_query_range("SELECT name FROM {users} WHERE LOWER(name) LIKE LOWER('%%%s%%') AND uid <> 0 ORDER BY name", $string, 0, 10);
      while ($row = db_fetch_object($result)) {
        $matches[$row->name] = check_plain($row->name);
      }
    }
  }
  drupal_json($matches);
}

/**
 * Function used by uasort to sort structured arrays by title.
 */
function _field_permissions_sort_fields($a, $b) {
  $a_text = (is_array($a) && isset($a['widget']['label'])) ? $a['widget']['label'] : '';
  $b_text = (is_array($b) && isset($b['widget']['label'])) ? $b['widget']['label'] : '';
  return strcasecmp($a_text, $b_text);
}

/**
 * Menu callback; Field permissions troubleshooting form.
 */
function field_permissions_troubleshooting_form(&$form_state, $nid = NULL, $field_name = NULL, $uid = NULL) {
//print '<pre>'. check_plain(var_export($rows, TRUE)) .'</pre>';
  $form = array();
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Report options'),
  );
  $node = (!empty($nid) ? node_load($nid) : NULL);
  $form['options']['node'] = array(
    '#type' => 'textfield',
    '#title' => t('Node'),
    '#default_value' => (!empty($node->nid) ? $node->title ." [nid:$node->nid]" : ''),
    '#autocomplete_path' => 'field_permissions/autocomplete/nodes',
    '#required' => TRUE,
    '#description' => t('Select the node that you want to check access to.'),
  );
  if (!empty($node->nid)) {
    $form['#node'] = $node;
  }

  if (empty($node->type)) {
    $form['options']['next'] = array(
      '#type' => 'submit',
      '#value' => t('Next'),
    );
    return $form;
  }

  $content_type = content_types($node->type);
  $content_fields = $content_type['fields'];
  uasort($content_fields, '_field_permissions_sort_fields');
  $fields = array('' => '-- '. t('Select field') .' --');
  foreach ($content_fields as $field) {
    $fields[$field['field_name']] = t('@field-label (@field-name)', array('@field-label' => $field['widget']['label'], '@field-name' => $field['field_name']));
  }
  $field = (!empty($field_name) && !empty($content_fields[$field_name]) ? $content_fields[$field_name] : NULL);
  $form['options']['field'] = array(
    '#type' => 'select',
    '#title' => t('Field'),
    '#options' => $fields,
    '#default_value' => (!empty($field) ? $field_name : NULL),
    '#description' => t('Emulate access to the given node.'),
    '#description' => t('Select the field on the selected node that you want to check access to.'),
  );
  $form['#field'] = $field;

  $account = (!empty($uid) ? user_load($uid) : ($uid == 0 ? drupal_anonymous_user() : NULL));
  $form['options']['user'] = array(
    '#type' => 'textfield',
    '#title' => t('User'),
    '#size' => 30,
    '#maxlength' => 60,
    '#default_value' => (!empty($account->name) ? $account->name : ''),
    '#autocomplete_path' => 'field_permissions/autocomplete/users',
    '#description' => t('Select the user that you want to check. Access to the specified node will be checked using all different roles assigned to this user. Leave blank to check for %anonymous.', array('%anonymous' => variable_get('anonymous', t('Anonymous')))),
  );
  $form['#account'] = (!empty($account->uid) ? $account : NULL);

  if (!empty($form['#field'])) {
    $form['options']['#collapsible'] = $form['options']['#collapsed'] = TRUE;
  }
  $form['options']['node']['#disabled'] = TRUE;
  $form['options']['node']['#value'] = $form['options']['node']['#default_value'];

  $form['options']['check'] = array(
    '#type' => 'submit',
    '#value' => t('Check'),
  );
  $form['options']['reset'] = array(
    '#type' => 'submit',
    '#value' => t('Reset'),
  );

  if (!empty($form['#field'])) {
    if (!isset($account->uid)) {
      $account = drupal_anonymous_user();
      $account->name = variable_get('anonymous', t('Anonymous'));
    }
    if ($node->uid == $account->uid) {
      $node_author = $account->name;
    }
    else {
      $node_account = user_load($node->uid);
      $node_author = $node_account->name;
    }
    $nodetype_name = node_get_types('name', $node->type);
    if (module_exists('i18ncontent')) {
      $nodetype_name = tt("nodetype:type:$node->type:name", $nodetype_name);
    }
    $form['report'] = array(
      '#type' => 'fieldset',
      '#title' => t('Report'),
      '#description' => t('This report simulates different operations to access the field %field-label in the node %node-title (nid: @node-nid), created by %node-author (uid: @node-uid), or creation of nodes of type %node-type, for each role assigned to user %user-name (uid: @uid). Move the mouse over each status icon to review detailed information about each test.', array(
        '%field-label' => (!empty($field['widget']['label']) ? $field['widget']['label'] : $field['field_name']),
        '%node-title' => $node->title,
        '@node-nid' => $node->nid,
        '%node-author' => $node_author,
        '@node-uid' => $node->uid,
        '%node-type' => $nodetype_name,
        '%user-name' => $account->name,
        '@uid' => $account->uid,
      )),
    );
  }

  return $form;
}

/**
 * Validate callback for the Field permissions troubleshooting form.
 */
function field_permissions_troubleshooting_form_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Reset')) {
    return;
  }

  // Validate the node.
  $value = $form_state['values']['node'];
  preg_match('`^(?:\s*|(.*) )?\[\s*nid\s*:\s*([0-9]+)\s*\]\s*$`', $value, $matches);
  if (empty($matches)) {
    // No explicit nid.
    if (!($node = node_load(array('title' => $value)))) {
      form_set_error('node', t('Node: found no valid post with that title.'));
      return;
    }
  }
  else {
    // Explicit [nid:n].
    list(, $title, $nid) = $matches;
    if (($node = node_load($nid)) && !empty($title) && trim($title) != trim($node->title)) {
      form_set_error('node', t('Node: title mismatch. Please check your selection.'));
      return;
    }
    elseif (empty($node->nid)) {
      form_set_error('node', t('Node: found no valid post with that title.'));
      return;
    }
  }
  $form_state['values']['nid'] = $node->nid;

  // Validate the field against the node type.
  if (!empty($form['options']['field'])) {
    if (empty($form_state['values']['field'])) {
      form_set_error('field', t('Field: please, select a field.'));
      return;
    }
    else {
      $content_type = content_types($node->type);
      if (empty($content_type['fields'][$form_state['values']['field']])) {
        form_set_error('field', t('Field: %field does not exist in the selected node type.', array('%field' => $form['options']['field']['#options'][$form_state['values']['field']])));
        return;
      }
    }
  }

  // Validate the user.
  if (!empty($form_state['values']['user'])) {
    if (!($account = user_load(array('name' => $form_state['values']['user'])))) {
      form_set_error('user', t('User: user %name cannot be found.', array('%name' => $form_state['values']['user'])));
      return;
    }
    $form_state['values']['uid'] = $account->uid;
  }
}

/**
 * Submit callback for the Field permissions troubleshooting form.
 */
function field_permissions_troubleshooting_form_submit($form, &$form_state) {
  $url = 'admin/structure/field_permissions/troubleshooting';
  if ($form_state['values']['op'] != t('Reset')) {
    $url .= '/'. $form_state['values']['nid'] .'/'. $form_state['values']['field'];
    if (!empty($form_state['values']['uid'])) {
      $url .= '/'. $form_state['values']['uid'];
    }
  }
  $form_state['redirect'] = $url;
}

/**
 * Render the Field permissions troubleshooting form.
 */
function theme_field_permissions_troubleshooting_form($form) {
  // Stop rendering if form has errors or no options have been supplied.
  if (form_get_errors() || empty($form['#node']) || empty($form['#field'])) {
    return drupal_render($form);
  }

  // Send javascript and stylesheets used for the troubleshooting report.
  $module_path = drupal_get_path('module', 'field_permissions');
  drupal_add_css($module_path .'/css/field_permissions.admin.css');
  drupal_add_js($module_path .'/js/field_permissions.tooltip.js');

  // Check access to the given field in the given node by the selected user.
  $base_node = &$form['#node'];
  $field = &$form['#field'];

  $base_account = (!empty($form['#account']) ? $form['#account'] : drupal_anonymous_user());
  $modules = module_implements('field_access');
  $permissions_list = field_permissions_list();
  $user_roles = ($base_account->uid == 1 ? array(-1 => t('site administrator (uid: 1)')) : $base_account->roles);

  $headers = array(t('User role'));
  foreach ($permissions_list as $permission_type => $permission_info) {
    $headers[] = array('data' => $permission_info['label'], 'class' => 'field-permissions-header');
  }
  $rows = array();
  foreach ($user_roles as $rid => $role_name) {
    $row = array(check_plain($role_name));
    foreach ($permissions_list as $permission_type => $permission_info) {
      // Prepare the user account.
      if ($rid == DRUPAL_ANONYMOUS_RID) {
        $testing_account = drupal_anonymous_user();
        $testing_account->name = variable_get('anonymous', t('Anonymous'));
      }
      else {
        $testing_account = drupal_clone($base_account);
        $testing_account->roles = array(DRUPAL_AUTHENTICATED_RID => $testing_account->roles[DRUPAL_AUTHENTICATED_RID]);
        if ($testing_account->uid != 1 && $rid != DRUPAL_AUTHENTICATED_RID) {
          $testing_account->roles[$rid] = $role_name;
        }
      }
      // Reset the static storage in user_access().
      user_access('access content', $testing_account, TRUE);

      // Prepare the node.
      $testing_node = drupal_clone($base_node);

      // Prepare the results.
      $results = array();
      $result = TRUE;

      if ($permission_type == 'view' || $permission_type == 'view own') {
        $op = 'view';
        $result = $results['node_access(view)'] = node_access('view', $testing_node, $testing_account);
      }
      else {
        $op = 'edit';
        if ($permission_type == 'create') {
          unset($testing_node->nid);
          $result = $results['node_access(create)'] = node_access('create', $testing_node->type, $testing_account);
        }
        else {
          $result = $results['node_access(update)'] = node_access('update', $testing_node, $testing_account);
        }
      }

      // Check access to field only when node access is granted.
      if ($result !== FALSE) {
        foreach ($modules as $module) {
          $key = $module .'_field_access('. $op .')';
          $results[$key] = module_invoke($module, 'field_access', $op, $field, $testing_account, $testing_node);
          if ($results[$key] === FALSE) {
            $result = FALSE;
          }
        }
      }

      if ($result !== FALSE) {
        $status = 'on';
        $title = t('Access allowed');
      }
      else {
        $status = 'off';
        $title = t('Access denied');
      }
      $icon = '<span class="field-permissions-status field-permissions-status-'. $status .'" title="'. check_plain($title) .'"></span>';
      $items = array();
      foreach ($results as $key => $result) {
        $items[] = $key .':&nbsp;'. check_plain(strtoupper(var_export($result, TRUE)));
      }
      $items = (!empty($items) ? '<div class="field-permissions-tooltip">'. theme('item_list', $items, t('Detailed results for %role -vs- %operation', array('%role' => $role_name, '%operation' => $permission_info['label']))) .'</div>' : '');
      $row[] = array('data' => $icon . $items, 'class' => 'field-permissions-cell');
    }
    $rows[] = $row;
  }

  $form['report']['table'] = array(
    '#type' => 'markup',
    '#value' => theme('table', $headers, $rows),
  );
  $output = drupal_render($form);

  return $output;
}
