<?php
/**
 * @file
 *  Contains FieldPermissionsTest.php
 */

namespace Drupal\field_permissions\Tests;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Indicates that a field does not have any access control.
 */
//define('FIELD_PERMISSIONS_PUBLIC', 0);
/**
 * Indicates that a field is private.
 *
 * Private fields are never displayed, and are only editable by the author (and
 * by site administrators with the 'access private fields' permission).
 */
//define('FIELD_PERMISSIONS_PRIVATE', 1);
/**
 * Indicates that a field has custom permissions.
 */
//define('FIELD_PERMISSIONS_CUSTOM', 2);

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
class FieldPermissionsUserTest extends FieldPermissionsTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  // public static $modules = ['node', 'field', 'field_ui', 'user', 'field_permissions'];

  /**
   * Simpletest's setUp().
   *
   * We want to be able to subclass this class, so we jump
   * through a few hoops in order to get the modules from args
   * and add our own.
   */
  public function setUp() {
    parent::setUp();
    $this->field_name = 'field_text';
    $this->field_text = "TEST BOSY IN USER PAGE";
  }

  /**
   * {@inheritdoc}
   */
  protected function getModule() {
    return 'field_permission';
  }

  private function TestUserAddField() {
    FieldStorageConfig::create(array(
      'field_name' => $this->field_name,
      'entity_type' => 'user',
      'type' => 'text',
    ))->save();

    FieldConfig::create(array(
      'field_name' => $this->field_name,
      'entity_type' => 'user',
      'label' => 'Textfield',
      'bundle' => 'user',
    ))->save();

    entity_get_form_display('user', 'user', 'default')
      ->setComponent($this->field_name)
      ->save();

    entity_get_form_display('user', 'user', 'register')
      ->setComponent($this->field_name)
      ->save();

    entity_get_display('user', 'user', 'default')
      ->setComponent($this->field_name)
      ->save();
  }

  private function TestUserFieldAdminFieldPermission() {
    // Control permission on field page.
    $this->drupalGet('admin/config/people/accounts/fields/user.user.' . $this->field_name);
    $this->assertNoText('Field visibility and permissions');
    // Add perm to user.
    $this->TestPremissionFormUi($this->adminUserRole, "admin_field_permissions");
    $this->drupalGet('admin/config/people/accounts/fields/user.user.' . $this->field_name);
    $this->assertText('Field visibility and permissions');
  }

  private function TestUserFieldFormAdd($user) {
    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->assertText('Textfield');
    $edit = array();
    $edit[$this->field_name . '[0][value]'] = $this->field_text;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('user/' . $user->id());
    $this->assertText($this->field_text);
  }

  private function TestViewUserField($flag, $user) {
    $this->drupalGet('user/' . $user->id());
    if ($flag == TRUE) {
      $this->assertText("Textfield");
    }
    else {
      $this->assertNoText("Textfield");
    }
  }

  private function TestEditUser($flag, $user) {
    $this->drupalGet('user/' . $user->id() . '/edit');
    if ($flag == TRUE) {
      $this->assertText("Textfield");
    }
    else {
      $this->assertNoText("Textfield");
    }
  }

  /**
   * Test chanege parmission field enable perm by rule.
   */
  private function TestFieldChangePermissionUserField($perm = FIELD_PERMISSIONS_PUBLIC, $custom_permission = array()) {
    $this->drupalGet('admin/config/people/accounts/fields/user.user.' . $this->field_name);
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
   * Test PUBLIC - view_own and edit_own field.
   */
  private function TestUserViewEditOwnField() {
    $permission = array();
    // AGGIUNGE I PERMESSI DI VIEW_OWN. all'utente limitato.
    $this->drupalLogin($this->adminUser);
    $perm = array('view_own_' . $this->field_name);
    $permission = $this->TestCreateCustomPermission($this->limitUserRole, $perm, $permission);
    $this->TestFieldChangePermissionUserField(FIELD_PERMISSIONS_CUSTOM, $permission);
    debug("[admin] view/edit profile limit user (false)");
    $this->TestViewUserField(FALSE, $this->limitedUser);
    $this->TestEditUser(FALSE, $this->limitedUser);
    debug("[admin] view/edit your profile (false)");
    $this->TestEditUser(FALSE, $this->adminUser);
    $this->TestViewUserField(FALSE, $this->adminUser);
    $this->drupalLogout();

    $this->drupalLogin($this->limitedUser);
    debug("[Limituser] view your profile (true)");
    $this->TestViewUserField(TRUE, $this->limitedUser);
    debug("[Limituser] view admin profile (false)");
    $this->TestViewUserField(FALSE, $this->adminUser);
    debug("[Limituser] edit your profile false");
    $this->TestEditUser(FALSE, $this->limitedUser);
    $this->drupalLogout();

    // AGGIUNGE I PERMESSI DI EDIT_OWN to limitUserRole.
    $this->drupalLogin($this->adminUser);
    $permission = $this->TestCreateCustomPermission($this->limitUserRole, array('edit_own_' . $this->field_name), $permission);
    $this->TestFieldChangePermissionUserField(FIELD_PERMISSIONS_CUSTOM, $permission);
    debug("[admin] edit your profile (false)");
    $this->TestEditUser(FALSE, $this->adminUser);
    debug("[admin] edit limit profile (false)");
    $this->TestEditUser(FALSE, $this->limitedUser);
    $this->drupalLogout();

    $this->drupalLogin($this->limitedUser);
    debug("[Limituser] edit your profile (true)");
    $this->TestEditUser(TRUE, $this->limitedUser);
    $this->drupalLogout();

  }

  private function TestUserViewEditAllField() {

    $permission = array();
    // AGGIUNGE I PERMESSI DI VIEW_OWN. all'utente limitato.
    $this->drupalLogin($this->adminUser);
    $perm = array('view_' . $this->field_name);
    $permission = $this->TestCreateCustomPermission($this->adminUserRole, $perm, $permission);
    $this->TestFieldChangePermissionUserField(FIELD_PERMISSIONS_CUSTOM, $permission);
    $this->TestViewUserField(TRUE, $this->limitedUser);

    $perm = array('edit_' . $this->field_name);
    $permission = $this->TestCreateCustomPermission($this->adminUserRole, $perm, $permission);
    $this->TestFieldChangePermissionUserField(FIELD_PERMISSIONS_CUSTOM, $permission);
    $this->TestEditUser(TRUE, $this->limitedUser);

    $this->drupalLogout();
  }

  public function TestFieldPrivate() {
    $this->drupalLogin($this->adminUser);
    $this->TestFieldChangePermissionUserField(FIELD_PERMISSIONS_PRIVATE);
    $this->drupalLogout();

    $this->drupalLogin($this->limitedUser);
    // Controlla il perofilo dell'utente admin e non deve vedere il campo.
    $this->TestViewUserField(FALSE, $this->adminUser);
    // Compila il campo per l'utente Limited.
    $this->TestUserFieldFormAdd($this->limitedUser);
    // Controlla che sia visibile.
    $this->TestViewUserField(TRUE, $this->limitedUser);
    $this->drupalLogout();

    $this->drupalLogin($this->adminUser);
    $this->TestViewUserField(FALSE, $this->limitedUser);
    $this->TestEditUser(FALSE, $this->limitedUser);
    $this->drupalLogout();
  }

  /**
   * Test execute().
   */
  public function testFieldPremissionUser() {

    $this->drupalLogin($this->adminUser);
    // setup.
    $this->TestUserAddField();
    $this->TestUserFieldAdminFieldPermission();
    // Compila il campo per l'utente admin.
    $this->TestUserFieldFormAdd($this->adminUser);
    $this->drupalLogout();

    // Controllo che si visibile ad altri utenti.
    $this->drupalLogin($this->limitedUser);
    $this->TestViewUserField(TRUE, $this->adminUser);
    $this->drupalLogout();

    $this->TestFieldPrivate();
    $this->TestUserViewEditOwnField();
    $this->TestUserViewEditAllField();

  }

}
