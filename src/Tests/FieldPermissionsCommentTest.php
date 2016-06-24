<?php
/**
 * @file
 *  Contains FieldPermissionsTest.php
 */

namespace Drupal\field_permissions\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

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
class FieldPermissionsCommentTest extends FieldPermissionsTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  // public static $modules = ['node', 'field', 'field_ui', 'user', 'field_permissions'];
  //private $nodeTest = NULL;
  /**
   * Simpletest's setUp().
   *
   * We want to be able to subclass this class, so we jump
   * through a few hoops in order to get the modules from args
   * and add our own.
   */
  private function NodeAddCommentField() {
    $entity_manager = \Drupal::entityManager();
    $bundle = 'article';
    $comment_type_storage = $entity_manager->getStorage('comment_type');

    $comment_type_id = 'comment';
    $entity_type = 'node';
    $field_name = 'comment';

    $comment_type_storage->create(array(
      'id' => $comment_type_id,
      'label' => 'Comment',
      'target_entity_type_id' => $entity_type,
      'description' => 'Default comment field',
    ))->save();

    $comment_type = $comment_type_storage->load($comment_type_id);

    $entity_manager->getStorage('field_storage_config')->create(array(
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'type' => 'comment',
      'settings' => array(
        'comment_type' => $comment_type_id,
      ),
    ))->save();
    $entity_manager->getStorage('field_config')->create(array(
      'label' => 'Comments',
      'description' => '',
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => 1,
      'default_value' => array(
        array(
          'status' => 2,
          'cid' => 0,
          'last_comment_name' => '',
          'last_comment_timestamp' => 0,
          'last_comment_uid' => 0,
        ),
      ),
    ))->save();
    // Entity form displays: assign widget settings for the 'default' form
    // mode, and hide the field in all other form modes.
    entity_get_form_display($entity_type, $bundle, 'default')
      ->setComponent($field_name, array(
        'type' => 'comment_default',
        'weight' => 20,
      ))
      ->save();
    // Entity view displays: assign widget settings for the 'default' view
    // mode, and hide the field in all other view modes.
    entity_get_display($entity_type, $bundle, 'default')
      ->setComponent($field_name, array(
        'label' => 'above',
        'type' => 'comment_default',
        'weight' => 20,
      ))
      ->save();
    $field = $entity_manager->getStorage('field_config')->create(array(
      'label' => 'Comment',
      'bundle' => $comment_type_id,
      'required' => TRUE,
      'field_storage' => FieldStorageConfig::loadByName('comment', 'comment_body'),
    ));
    $field->save();
    // Assign widget settings for the 'default' form mode.
    entity_get_form_display('comment', $comment_type_id, 'default')
      ->setComponent('comment_body', array(
        'type' => 'text_textarea',
      ))
      ->save();
    // Assign display settings for the 'default' view mode.
    entity_get_display('comment', $comment_type_id, 'default')
      ->setComponent('comment_body', array(
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ))
      ->save();
  }

  public function setUp() {
    parent::setUp();
    $this->field_name = 'comment_body';
    $this->subject = "Test subject comment";
    $this->field_text = "Test Body comment in node page.";
  }

  /**
   * Test chanege parmission field enable perm by rule.
   */
  private function TestFieldChangePermissionCommentField($perm = FIELD_PERMISSIONS_PUBLIC, $custom_permission = array(), $path) {

    $this->drupalGet($path);
    //$this->drupalGet('admin/structure/types/manage/article/fields/node.article.comment');
    // $this->drupalGet('node/add/article');
    // $this->drupalGet('admin/config/people/accounts/fields/user.user.' . $this->field_name);
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

  private function TestCommentFieldBase() {
    $edit = array();
    $this->drupalLogin($this->adminUser);
    // Node add.
    $this->drupalGet('node/add/article');
    $this->nodeTest = $this->drupalCreateNode(array('type' => 'article', 'uid' => $this->limitedUser->id()));
    $this->drupalGet('node/' . $this->nodeTest->id());
    // Add comment to node.
    $edit['subject[0][value]'] = $this->subject;
    $edit['comment_body[0][value]'] = $this->field_text;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('node/' . $this->nodeTest->id());
    $this->assertText($this->field_text);
    $this->assertText($this->subject);
    $this->drupalLogout();

    $this->drupalLogin($this->limitedUser);
    // Test visibility first comment by admin.
    $this->drupalGet('node/' . $this->nodeTest->id());
    $this->assertText($this->field_text);
    $this->assertText($this->subject);
    // Add second comment to node.
    $edit = array();
    $edit['subject[0][value]'] = 'Limit User comment subject';
    $edit['comment_body[0][value]'] = 'Limit User comment body';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('node/' . $this->nodeTest->id());
    // Test visibility second comment by limituser..
    $this->assertText('Limit User comment subject');
    $this->assertText('Limit User comment body');
    $this->drupalLogout();
  }

  private function TestCommentFieldPrivate($bundle = 'comment') {
    if ($bundle == 'comment') {
      $path = 'admin/structure/comment/manage/comment/fields/comment.comment.comment_body';
      $permission = array();
      $this->drupalLogin($this->adminUser);
      // Add perm to admin (admin field permissions).
      $this->TestPremissionFormUi($this->adminUserRole, "admin_field_permissions");
      // Set Private field to comment body.
      $this->TestFieldChangePermissionCommentField(FIELD_PERMISSIONS_PRIVATE, $permission, $path);
      $this->drupalLogout();
      $this->drupalLogin($this->limitedUser);
      $this->drupalGet('node/' . $this->nodeTest->id());
      // Test hide body comment post by Adminuser but display subject..
      $this->assertText($this->subject);
      $this->assertNoText($this->field_text);
      // Test view your comment.
      $this->assertText('Limit User comment subject');
      $this->assertText('Limit User comment body');
      // Test edit your comment.
      $this->drupalGet('comment/2/edit');
      $this->assertText('Limit User comment body');

      $this->drupalLogin($this->adminUser);
      // Admin edit comment post to Limit user.
      $this->drupalGet('comment/2/edit');
      // Hide comment body to edit comment.
      $this->assertNoText('Limit User comment body');
      $this->drupalLogout();
    }

  }

  private function TestCommentFieldCustom($bundle = 'comment') {
    $path = 'admin/structure/comment/manage/comment/fields/comment.comment.comment_body';
    $permission = array();
    $this->drupalLogin($this->adminUser);
    // Change custom permission view own field body.
    $perm = array('view_own_' . $this->field_name);
    $permission = $this->TestCreateCustomPermission($this->limitUserRole, $perm, $permission);
    $this->TestFieldChangePermissionCommentField(FIELD_PERMISSIONS_CUSTOM, $permission, $path);
    $this->drupalLogout();

    $this->drupalLogin($this->limitedUser);
    // View your comment but not view field body comment post by admin.
    $this->drupalGet('node/' . $this->nodeTest->id());
    // Hide body comment post by Adminuser.
    $this->assertNoText($this->field_text);
    $this->assertText($this->subject);
    $this->assertText('Limit User comment subject');
    $this->assertText('Limit User comment body');
    // Edit your comment not accesss to body field.
    $this->drupalGet('comment/2/edit');
    $this->assertNoText('Limit User comment body');
    $this->drupalLogout();

    $this->drupalLogin($this->adminUser);
    // Custom permission add edit_own field body.
    $perm = array('edit_own_' . $this->field_name);
    $permission = $this->TestCreateCustomPermission($this->limitUserRole, $perm, $permission);
    $this->TestFieldChangePermissionCommentField(FIELD_PERMISSIONS_CUSTOM, $permission, $path);
    $this->drupalLogout();

    $this->drupalLogin($this->limitedUser);
    // Test edit your comment edit field body.
    $this->drupalGet('comment/2/edit');
    $this->assertText('Limit User comment body');
    $this->drupalLogout();

    $this->drupalLogin($this->adminUser);
    // Add edit and view all comment.
    $perm = array('edit_' . $this->field_name, 'view_' . $this->field_name);
    $permission = $this->TestCreateCustomPermission($this->adminUserRole, $perm, $permission);
    $this->TestFieldChangePermissionCommentField(FIELD_PERMISSIONS_CUSTOM, $permission, $path);
    // view.
    $this->drupalGet('node/' . $this->nodeTest->id());
    $this->assertText('Limit User comment body');
    // edit.
    $this->drupalGet('comment/2/edit');
    $this->assertText('Limit User comment body');
    $this->drupalLogout();
  }

  private function TestAccessPrivateFied($bundle = 'comment') {
    $path = 'admin/structure/comment/manage/comment/fields/comment.comment.comment_body';
    $permission = array();
    $this->drupalLogin($this->adminUser);
    $this->TestFieldChangePermissionCommentField(FIELD_PERMISSIONS_PRIVATE, $permission, $path);
    // View.
    $this->drupalGet('node/' . $this->nodeTest->id());
    $this->assertNoText('Limit User comment body');
    // Edit.
    $this->drupalGet('comment/2/edit');
    $this->assertNoText('Limit User comment body');
    // Add permission access user private field.
    $this->TestPremissionFormUi($this->adminUserRole, "access_user_private_field");
    // View.
    $this->drupalGet('node/' . $this->nodeTest->id());
    $this->assertText('Limit User comment body');
    // Edit.
    $this->drupalGet('comment/2/edit');
    $this->assertText('Limit User comment body');

    $this->drupalLogout();
  }

  /**
   * Test execute().
   */
  public function testFieldPremissionComment() {

    $edit = array();
    $permission = array();

    $this->NodeAddCommentField();
    $this->TestCommentFieldBase();

    $this->TestCommentFieldPrivate();
    $this->TestAccessPrivateFied();

    $this->TestCommentFieldCustom();
    // $this->TestAccessPrivateFied();
    //$this->TestCommentFieldPrivate('node');

  }

}
