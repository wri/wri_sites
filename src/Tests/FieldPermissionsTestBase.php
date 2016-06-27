<?php

namespace Drupal\field_permissions\Tests;

use Drupal\user\Entity\Role;
use Drupal\field_permissions\FieldPermissionsService;
use Drupal\simpletest\WebTestBase;

/**
 * A generic field testing class.
 *
 * Subclass this one to test your specific field type
 * and get some basic unit testing for free.
 *
 * Since Simpletest only looks through one class definition
 * to find test functions, we define generic tests as
 * 'code_testWhatever' or 'form_testWhatever'. Subclasses
 * can then implement shim test methods that just call the
 * generic tests.
 *
 * 'code_' and 'form_' prefixes denote the type of test:
 * using code only, or through Drupal page forms.
 */
abstract class FieldPermissionsTestBase extends WebTestBase {

  // Our tests will generate some random field instance
  // names. We store them here so many functions can act on them.
  protected $instanceNames;

  /**
   * An administrative user with permission to configure comment settings.
   *
   * @var \Drupal\user\AccountInterface
   */
  protected $adminUser;
  /**
   * An limit user.
   *
   * @var \Drupal\user\AccountInterface
   */
  protected $limitedUser;
  /**
   * A normal user with permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;
  protected $adminUserRole;
  protected $limitUserRole;
  protected $nodeTest;


  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node', 'field', 'comment', 'field_ui', 'user', 'field_permissions'];

  /**
   * Simpletest's setUp().
   *
   * We want to be able to subclass this class, so we jump
   * through a few hoops in order to get the modules from args
   * and add our own.
   */
  public function setUp() {
    parent::setUp();

    // Create node type.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));
    $this->checkPermissions(array("create article content"), TRUE);
    $this->adminUser = $this->drupalCreateUser(array(
      'administer content types',
      'access user profiles',
      'access content',
      'administer users',
      'administer user fields',
      'administer account settings',
      'administer permissions',
      'administer node fields',
      'bypass node access',
      'administer nodes',
      'create article content',
      'administer comment types',
      'administer comment fields',
      'administer comments',
      'post comments',
      'skip comment approval',
      'access comments',

    ));

    $this->limitedUser = $this->drupalCreateUser(array(
      'access content',
      'access user profiles',
      'create article content',
      'edit any article content',
      'post comments',
      'skip comment approval',
      'access comments',
      'edit own comments',
    ));

    $this->webUser = $this->drupalCreateUser(array(
      'access content',
      'create article content',
      'edit any article content',
    ));

    $this->adminUserRole = Role::load($this->adminUser->getRoles(array('authenticated'))[0]);
    $this->limitUserRole = Role::load($this->limitedUser->getRoles(array('authenticated'))[0]);
    $this->webUserRole = Role::load($this->limitedUser->getRoles(array('authenticated'))[0]);

  }

  /**
   * {@inheritdoc}
   * protected function getModule() {
   * return 'field_permission';
   * }.
   */

  /**
   * Test Send form permission page width enable permission by rules.
   */
  public function TestPremissionFormUi($rule, $perm) {
    $edit = array();
    $edit[$rule->id() . '[' . $perm . ']'] = TRUE;
    $this->drupalGet('admin/people/permissions');
    $this->drupalPostForm(NULL, $edit, t('Save permissions'));
    drupal_static_reset('user_access');
    drupal_static_reset('user_role_permissions');
    $this->assertText(t('The changes have been saved.'), t('Successful save message displayed.'));
  }

  /**
   * Test case.
   */
  function TestGetCustomPemrission($role, $field_perm = array()) {
    $custom_perm = array();
    $permission_list = FieldPermissionsService::permissions();
    $permission_list = array_keys($permission_list);
    $permission_role = array_keys(user_roles());
    $remove_perm = array();
    // Set all check to false.
    foreach ($permission_role as $rname) {
      foreach ($permission_list as $perm) {
        $key = 'permissions[' . $perm . '][' . $rname . ']';
        $custom_perm[$key] = FALSE;
      }
    }
    // Set perm check to true.
    foreach ($field_perm as $perm) {
      $key = 'permissions[' . $perm . '][' . $role->id() . ']';
      $custom_perm[$key] = TRUE;
    }
    return $custom_perm;
  }

  /**
   * Test case.
   */
  function TestCreateCustomPermission($role, $permissions = array(), $custom_permission = array()) {
    // debug($custom_permission);
    $tmp = $this->TestGetCustomPemrission($role, $permissions);
    foreach ($tmp as $key => $val) {
      if (isset($custom_permission[$key]) && $custom_permission[$key] === TRUE) {
        $tmp[$key] = TRUE;
      }
    }
    return $tmp;
    // debug(array_merge($this->TestGetCustomPemrission($role, $permissions), $custom_permission));
    //  return array_merge($this->TestGetCustomPemrission($role, $permissions), $custom_permission);.
  }

  /**
   * Test case.
   *    * Function TestChangeCustomPermission($custom_permission) {
   * $this->drupalLogin($this->adminUser);
   * $this->TestFieldChangePermissionField(FIELD_PERMISSIONS_CUSTOM, $custom_permission);
   * $this->drupalLogout();
   * }.
  */

}
