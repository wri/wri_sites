<?php

namespace Drupal\Tests\field_permissions\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field_permissions\Functional\FieldPermissionsTestBase;
use Drupal\user\UserInterface;

/**
 * Test field permissions on users.
 *
 * @group field_permission
 */
class FieldPermissionsUserTest extends FieldPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->fieldName = Unicode::strtolower($this->randomMachineName());
    // Remove the '@' symbol so it isn't converted to an email link.
    $this->fieldText = str_replace('@', '', $this->randomString(42));

    // Allow the web user to administer user profiles.
    $this->webUserRole
      ->grantPermission('access user profiles')
      ->grantPermission('administer users')
      ->save();

    $this->addUserField();
  }

  /**
   * Test field permissions on user entities.
   */
  public function testUserFieldPermissions() {

    $this->drupalLogin($this->adminUser);
    // Compila il campo per l'utente admin.
    $this->_testUserFieldEdit($this->adminUser);
    $this->drupalLogout();

    // Controllo che si visibile ad altri utenti.
    $this->drupalLogin($this->limitedUser);
    $this->assertUserFieldAccess($this->adminUser);
    $this->drupalLogout();

    $this->_testPrivateField();
    $this->_testUserViewEditOwnField();
    $this->_testUserViewEditField();

  }

  /**
   * Adds a text field to the user entity.
   */
  protected function addUserField() {
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'user',
      'type' => 'text',
    ])->save();

    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'user',
      'label' => 'Textfield',
      'bundle' => 'user',
    ])->save();

    entity_get_form_display('user', 'user', 'default')
      ->setComponent($this->fieldName)
      ->save();

    entity_get_form_display('user', 'user', 'register')
      ->setComponent($this->fieldName)
      ->save();

    entity_get_display('user', 'user', 'default')
      ->setComponent($this->fieldName)
      ->save();
  }

  /**
   * Tests field permissions on the user edit form for a given account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to edit.
   */
  protected function _testUserFieldEdit(UserInterface $account) {
    $this->drupalGet($account->toUrl('edit-form'));
    $this->assertText('Textfield');
    $edit = [];
    $edit[$this->fieldName . '[0][value]'] = $this->fieldText;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet($account->toUrl());
    $this->assertEscaped($this->fieldText);
  }

  /**
   * Verify the test field is accessible when viewing the given user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to verify field permissions for viewing.
   */
  protected function assertUserFieldAccess(UserInterface $account) {
    $this->drupalGet($account->toUrl());
    $this->assertText('Textfield');
  }

  /**
   * Verify the test field is not accessible when viewing the given user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to verify field permissions for viewing.
   */
  protected function assertUserFieldNoAccess(UserInterface $account) {
    $this->drupalGet($account->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoText('Textfield');
  }

  /**
   * Verifies that the current logged in user can edit the user field.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to edit.
   */
  protected function assertUserEditFieldAccess(UserInterface $account) {
    $this->drupalGet($account->toUrl('edit-form'));
    $this->assertText('Textfield');
  }

  /**
   * Verifies that the current logged in user cannot edit the user field.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to edit.
   */
  protected function assertUserEditFieldNoAccess(UserInterface $account) {
    $this->drupalGet($account->toUrl('edit-form'));
    $this->assertResponse(200);
    $this->assertNoText('Textfield');
  }

  /**
   * Set user field permissions to the given type.
   *
   * @param int $perm
   *   The permission type to set.
   * @param array $custom_permission
   *   An array of custom permissions.
   */
  private function setUserFieldPermission($perm = FIELD_PERMISSIONS_PUBLIC, $custom_permission = []) {
    $current_user = $this->loggedInUser;
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/people/accounts/fields/user.user.' . $this->fieldName);
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
   * Test PUBLIC - view_own and edit_own field.
   */
  protected function _testUserViewEditOwnField() {
    $permission = [];
    // AGGIUNGE I PERMESSI DI VIEW_OWN. all'utente limitato.
    $this->drupalLogin($this->webUser);
    $perm = ['view own ' . $this->fieldName];
    $permission = $this->grantCustomPermissions($this->limitUserRole, $perm, $permission);
    $this->setUserFieldPermission(FIELD_PERMISSIONS_CUSTOM, $permission);
    // [admin] view/edit profile limit user (false).
    $this->assertUserFieldNoAccess($this->limitedUser);
    $this->assertUserEditFieldNoAccess($this->limitedUser);
    // [admin] view/edit your profile (false).
    $this->assertUserEditFieldNoAccess($this->adminUser);
    $this->assertUserFieldNoAccess($this->adminUser);
    $this->drupalLogout();

    $this->drupalLogin($this->limitedUser);
    // [Limited user] view your profile (true).
    $this->assertUserFieldAccess($this->limitedUser);
    // [Limited user] view admin profile (false).
    $this->assertUserFieldNoAccess($this->adminUser);
    // [Limited user] edit your profile false.
    $this->assertUserEditFieldNoAccess($this->limitedUser);
    $this->drupalLogout();

    // AGGIUNGE I PERMESSI DI EDIT_OWN to limitUserRole.
    $this->drupalLogin($this->webUser);
    $permission = $this->grantCustomPermissions($this->limitUserRole, ['edit own ' . $this->fieldName], $permission);
    $this->setUserFieldPermission(FIELD_PERMISSIONS_CUSTOM, $permission);
    // [admin] edit your profile (false).
    $this->assertUserEditFieldNoAccess($this->adminUser);
    // [admin] edit limit profile (false).
    $this->assertUserEditFieldNoAccess($this->limitedUser);
    $this->drupalLogout();

    $this->drupalLogin($this->limitedUser);
    // [Limited user] edit your profile (true).
    $this->assertUserEditFieldAccess($this->limitedUser);
    $this->drupalLogout();

  }

  /**
   * Tests custom permissions.
   */
  protected function _testUserViewEditField() {

    $permission = [];
    // AGGIUNGE I PERMESSI DI VIEW_OWN. all'utente limitato.
    $this->drupalLogin($this->webUser);
    $perm = ['view ' . $this->fieldName];
    $permission = $this->grantCustomPermissions($this->webUserRole, $perm, $permission);
    $this->setUserFieldPermission(FIELD_PERMISSIONS_CUSTOM, $permission);
    $this->assertUserFieldAccess($this->limitedUser);

    $perm = ['edit ' . $this->fieldName];
    $permission = $this->grantCustomPermissions($this->webUserRole, $perm, $permission);
    $this->setUserFieldPermission(FIELD_PERMISSIONS_CUSTOM, $permission);
    $this->assertUserEditFieldAccess($this->limitedUser);

    $this->drupalLogout();
  }

  /**
   * Test field access with private permissions.
   */
  protected function _testPrivateField() {
    $this->drupalLogin($this->webUser);
    $this->setUserFieldPermission(FIELD_PERMISSIONS_PRIVATE);
    $this->drupalLogout();

    $this->drupalLogin($this->limitedUser);
    // Controlla il perofilo dell'utente admin e non deve vedere il campo.
    $this->assertUserFieldNoAccess($this->adminUser);
    // Compila il campo per l'utente Limited.
    $this->_testUserFieldEdit($this->limitedUser);
    // Controlla che sia visibile.
    $this->assertUserFieldAccess($this->limitedUser);
    $this->drupalLogout();

    $this->drupalLogin($this->webUser);
    $this->assertUserFieldNoAccess($this->limitedUser);
    $this->assertUserEditFieldNoAccess($this->limitedUser);
    $this->drupalLogout();
  }

}
