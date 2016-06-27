<?php

namespace Drupal\field_permissions\Tests;

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
 *
 * @group field_permission
 */
class FieldPermissionsNodeTest extends FieldPermissionsTestBase {

  /**
   * Simpletest's setUp().
   *
   * We want to be able to subclass this class, so we jump
   * through a few hoops in order to get the modules from args
   * and add our own.
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Test chanege parmission field enable perm by rule.
   */
  public function TestFieldChangePermissionField($perm = FIELD_PERMISSIONS_PUBLIC, $custom_permission = array()) {
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.body');
    if ($perm == FIELD_PERMISSIONS_PUBLIC || $perm == FIELD_PERMISSIONS_PRIVATE) {
      $edit = array('type' => $perm);
      $this->drupalPostForm(NULL, $edit, t('Save settings'));
    }
    elseif ($perm == FIELD_PERMISSIONS_CUSTOM && !empty($custom_permission)) {
      $custom_permission['type'] = $perm;
      $this->drupalPostForm(NULL, $custom_permission, t('Save settings'));
    }
    drupal_static_reset('user_access');
    drupal_static_reset('user_role_permissions');
  }

  /**
   * Support node add by api.
   */
  public function TestNodeAdd() {
    $this->nodeTest = $this->drupalCreateNode(array('type' => 'article', 'uid' => $this->limitedUser->id()));
    $this->drupalGet('node/' . $this->nodeTest->id());
    $node_body = $this->nodeTest->getFields()['body']->getValue();
    $this->assertText($node_body[0]['value']);
  }

  /**
   * Test node add form..
   */
  public function TestNodeAddForm() {
    $this->drupalGet('node/add/article');
    $this->assertText('Body');
    $edit = array();
    $node_name = $this->randomMachineName();
    $edit['body[0][value]'] = $this->randomString();
    $edit['title[0][value]'] = $node_name;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Article @name has been created.', array('@name' => $node_name)));
  }

  /**
   * Test case view node.
   */
  public function TestViewNodeField($test = TRUE) {
    $field_value = $this->nodeTest->getFields()['body']->getValue();
    $this->drupalGet('node/' . $this->nodeTest->id());
    if ($test == TRUE) {
      $this->assertText($field_value[0]['value']);
    }
    else {
      $this->assertNoText($field_value[0]['value']);
    }
  }

  /**
   * Test case edit field.
   */
  public function TestEditNodeField($test = TRUE) {
    $this->drupalGet('node/' . $this->nodeTest->id() . '/edit');
    $this->assertText('Title');
    if ($test == TRUE) {
      $this->assertText('Body');
    }
    else {
      $this->assertNoText('Body');
    }
  }

  /**
   * Test case.
   */
  function TestChangeCustomPermission($custom_permission) {
    $this->drupalLogin($this->adminUser);
    $this->TestFieldChangePermissionField(FIELD_PERMISSIONS_CUSTOM, $custom_permission);
    $this->drupalLogout();
  }

  /**
   * Test case.
   */
  public function TestFieldEdit() {
    $this->drupalLogin($this->adminUser);
    // Test page without permissin [admin_field_permissions].
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.body');
    $this->assertNoText('Field visibility and permissions');
    $this->TestPremissionFormUi($this->adminUserRole, "admin_field_permissions");
    // Test page width permissin [admin_field_permissions].
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.body');
    $this->assertText('Field visibility and permissions');
    $this->drupalLogout();
  }

  /**
   * Test permiossion page.
   * nota : admin/people/permissions.
   */
  public function TestPermissionPage() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/people/permissions');
    $this->assertText('Access other users private fields');
    $this->assertText('Administer field permissions');
    $this->drupalLogout();
  }

  /**
   * Test create content.
   */
  function TestInitAddNode() {
    $this->drupalLogin($this->limitedUser);
    $this->TestNodeAddForm();
    $this->TestNodeAdd();
    $this->drupalLogout();
  }

  /**
   * Test PUBLIC - PRIVATE EDIT - VIEW.
   */
  function TestChengeToPrivateField() {
    $this->drupalLogin($this->adminUser);

    $this->TestViewNodeField(TRUE);

    $this->TestPremissionFormUi($this->adminUserRole, "admin_field_permissions");
    $this->TestFieldChangePermissionField(FIELD_PERMISSIONS_PRIVATE, NULL);
    $this->TestViewNodeField(FALSE);

    $this->TestPremissionFormUi($this->adminUserRole, "access_user_private_field");
    // debug($this->adminUserRole);.
    $this->TestViewNodeField(TRUE);
    $this->drupalLogout();
  }

  /**
   * Test PUBLIC - view own field.
   */
  function TestViewOwnField() {
    $permission = array();
    $permission = $this->TestCreateCustomPermission($this->limitUserRole, array("view_own_body"), $permission);
    $this->TestChangeCustomPermission($permission);

    // Login width author node.
    $this->drupalLogin($this->limitedUser);
    $this->TestViewNodeField(TRUE);
    $this->TestEditNodeField(FALSE);
    $this->drupalLogout();

    // Login webuser.
    $this->drupalLogin($this->webUser);
    $this->TestViewNodeField(FALSE);
    $this->TestEditNodeField(FALSE);
    $this->drupalLogout();
  }

  /**
   * Test PUBLIC - view own field.
   */
  function TestViewEditOwnField() {
    $permission = array();
    $permission = $this->TestCreateCustomPermission($this->limitUserRole, array("view_own_body", "edit_own_body"), $permission);
    $this->TestChangeCustomPermission($permission);

    // Login width author node.
    $this->drupalLogin($this->limitedUser);
    $this->TestViewNodeField(TRUE);
    $this->TestEditNodeField(TRUE);
    $this->drupalLogout();

    // Login webuser.
    $this->drupalLogin($this->webUser);
    $this->TestViewNodeField(FALSE);
    $this->TestEditNodeField(FALSE);
    $this->drupalLogout();

  }

  /**
   * Test - view edit all field.
   */
  public function TestViewEditAllField() {

    $this->drupalLogin($this->adminUser);
    $this->TestViewNodeField(FALSE);
    $this->TestEditNodeField(FALSE);
    $this->drupalLogout($this->adminUser);
    $permission = array();
    $permission = $this->TestCreateCustomPermission($this->adminUserRole, array("view_body", "edit_body"), $permission);
    $this->TestChangeCustomPermission($permission);

    $this->drupalLogin($this->adminUser);
    $this->TestViewNodeField(TRUE);
    $this->TestEditNodeField(TRUE);
    $this->drupalLogout();
  }

  /**
   * Test execute().
   */
  public function testTestPremissionUi() {

    $this->TestPermissionPage();
    $this->TestFieldEdit();
    $this->TestInitAddNode();

    debug("Test Private field");
    $this->TestChengeToPrivateField();
    debug("Test View own field");

    $this->TestViewOwnField();

    debug("Test View own and edit own field");
    $this->TestViewEditOwnField();
    debug("Test View any and edit any field");
    $this->TestViewEditAllField();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/people/permissions');

  }

}
