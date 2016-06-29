<?php

namespace Drupal\Tests\field_permissions\Functional;
use Drupal\Core\Url;
use Drupal\Tests\field_permissions\Functional\FieldPermissionsTestBase;

/**
 * Test field permissions on nodes.
 *
 * @group field_permission
 */
class FieldPermissionsNodeTest extends FieldPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Grant the web user permission to administer node fields.
    $this->webUserRole
      ->grantPermission('administer content types')
      ->grantPermission('administer node fields')
      ->save();
  }

  /**
   * Test field permissions on nodes.
   */
  public function testNodeFieldPermissions() {
    $this->_testPermissionPage();
    $this->_testFieldPermissionConfigurationEdit();
    $this->_testInitAddNode();
    $this->_testChengeToPrivateField();
    $this->_testViewOwnField();
    $this->_testViewEditOwnField();
    $this->_testViewEditAllField();
  }

  /**
   * Set the bode body field permissions to the given type.
   *
   * @param int $perm
   *   The permission type.
   * @param array $custom_permission
   *   An array of custom permissions.
   *
   * @todo Directly set the field permissions rather than using the UI.
   */
  protected function setNodeFieldPermissions($perm = FIELD_PERMISSIONS_PUBLIC, $custom_permission = []) {
    $current_user = $this->loggedInUser;
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.body');
    if ($perm == FIELD_PERMISSIONS_PUBLIC || $perm == FIELD_PERMISSIONS_PRIVATE) {
      $edit = ['type' => $perm];
      $this->drupalPostForm(NULL, $edit, t('Save settings'));
    }
    elseif ($perm == FIELD_PERMISSIONS_CUSTOM && !empty($custom_permission)) {
      $custom_permission['type'] = $perm;
      $this->drupalPostForm(NULL, $custom_permission, t('Save settings'));
    }
    if ($current_user) {
      $this->drupalLogin($current_user);
    }
  }

  /**
   * Create a node directly via the API.
   */
  protected function addNode() {
    $this->node = $this->drupalCreateNode(['type' => 'article', 'uid' => $this->limitedUser->id()]);
    $this->drupalGet('node/' . $this->node->id());
    $node_body = $this->node->getFields()['body']->getValue();
    $this->assertText($node_body[0]['value']);
  }

  /**
   * Create a node through the UI.
   */
  protected function addNodeUi() {
    $this->drupalGet('node/add/article');
    $this->assertText('Body');
    $edit = [];
    $node_name = $this->randomMachineName();
    $edit['body[0][value]'] = $this->randomString();
    $edit['title[0][value]'] = $node_name;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Article @name has been created.', ['@name' => $node_name]));
  }

  /**
   * Asserts that the body field is visible.
   */
  protected function assertNodeFieldVisible() {
    $field_value = $this->node->getFields()['body']->getValue();
    $this->drupalGet('node/' . $this->node->id());
    $this->assertText($field_value[0]['value']);
  }

  /**
   * Asserts that the body field is not visible.
   */
  protected function assertNodeFieldHidden() {
    $field_value = $this->node->getFields()['body']->getValue();
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoText($field_value[0]['value']);
  }

  /**
   * Asserts that the node field is editable.
   */
  protected function assertNodeFieldEditAccess() {
    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->assertText('Title');
    $this->assertText('Body');
  }

  /**
   * Asserts that the node field is not editable.
   */
  protected function assertNodeFieldEditNoAccess() {
    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->assertResponse(200);
    $this->assertText('Title');
    $this->assertNoText('Body');
  }

  /**
   * Test field permission configuration access.
   */
  protected function _testFieldPermissionConfigurationEdit() {
    $this->drupalLogin($this->webUser);
    // Test page without admin field permission.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.body');
    $this->assertResponse(200);
    $this->assertNoText('Field visibility and permissions');
    $this->webUserRole->grantPermission('admin_field_permissions')->save();
    // Test page with admin field permission.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.body');
    $this->assertText('Field visibility and permissions');
    $this->drupalLogout();
  }

  /**
   * Test permissions page.
   */
  protected function _testPermissionPage() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute('user.admin_permissions'));
    $this->assertText('Access other users private fields');
    $this->assertText('Administer field permissions');
    $this->drupalLogout();
  }

  /**
   * Test create content.
   */
  protected function _testInitAddNode() {
    $this->drupalLogin($this->limitedUser);
    $this->addNodeUi();
    $this->addNode();
    $this->drupalLogout();
  }

  /**
   * Test PUBLIC - PRIVATE EDIT - VIEW.
   */
  protected function _testChengeToPrivateField() {
    $this->drupalLogin($this->webUser);

    $this->assertNodeFieldVisible();

    $this->webUserRole->grantPermission('admin_field_permissions')->save();
    $this->setNodeFieldPermissions(FIELD_PERMISSIONS_PRIVATE, NULL);
    $this->assertNodeFieldHidden();

    $this->webUserRole->grantPermission('access_user_private_field')->save();
    $this->assertNodeFieldVisible();
    $this->drupalLogout();
  }

  /**
   * Test PUBLIC - view own field.
   */
  protected function _testViewOwnField() {
    $permission = [];
    $permission = $this->grantCustomPermissions($this->limitUserRole, ["view_own_body"], $permission);
    $this->setNodeFieldPermissions(FIELD_PERMISSIONS_CUSTOM, $permission);

    // Login width author node.
    $this->drupalLogin($this->limitedUser);
    $this->assertNodeFieldVisible();
    $this->assertNodeFieldEditNoAccess();
    $this->drupalLogout();

    // Login webuser.
    $this->drupalLogin($this->webUser);
    $this->assertNodeFieldHidden();
    $this->assertNodeFieldEditNoAccess();
    $this->drupalLogout();
  }

  /**
   * Test PUBLIC - view own field.
   */
  protected function _testViewEditOwnField() {
    $permission = [];
    $permission = $this->grantCustomPermissions($this->limitUserRole, ["view_own_body", "edit_own_body"], $permission);
    $this->setNodeFieldPermissions(FIELD_PERMISSIONS_CUSTOM, $permission);

    // Login width author node.
    $this->drupalLogin($this->limitedUser);
    $this->assertNodeFieldVisible();
    $this->assertNodeFieldEditAccess();
    $this->drupalLogout();

    // Login webuser.
    $this->drupalLogin($this->webUser);
    $this->assertNodeFieldHidden();
    $this->assertNodeFieldEditNoAccess();
    $this->drupalLogout();

  }

  /**
   * Test - view edit all field.
   */
  protected function _testViewEditAllField() {
    $this->drupalLogin($this->webUser);
    $this->assertNodeFieldHidden();
    $this->assertNodeFieldEditNoAccess();
    $this->drupalLogout();
    $permission = [];
    $permission = $this->grantCustomPermissions($this->webUserRole, ["view_body", "edit_body"], $permission);
    $this->setNodeFieldPermissions(FIELD_PERMISSIONS_CUSTOM, $permission);

    $this->drupalLogin($this->webUser);
    $this->assertNodeFieldVisible();
    $this->assertNodeFieldEditAccess();
    $this->drupalLogout();
  }

}
