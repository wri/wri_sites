<?php

/**
 * @file
 * Person module post_update implementations.
 */

use Drupal\node\Entity\Node;

/**
 * Adds a default Staff Grouping for all Persons.
 */
function wri_person_post_update_person_grouping(&$sandbox) {
  // Fill sandbox.
  // Work through Persons.
  if (!isset($sandbox['total'])) {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'person')
      ->notExists('field_staff_group')
      ->execute();
    $sandbox['total'] = count($nids);
    $sandbox['current'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $users_per_batch = 25;
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'person')
    ->notExists('field_staff_group')
    ->range(0, $users_per_batch)
    ->execute();
  if (empty($nids)) {
    $sandbox['#finished'] = 1;
    return;
  }

  // Load terms.
  $leader_id = \Drupal::entityQuery('taxonomy_term')
    ->condition('uuid', '51a8936a-2d62-4452-b5c2-1fd0b542e39e')
    ->execute();

  $staff_id = \Drupal::entityQuery('taxonomy_term')
    ->condition('uuid', '7bc441ea-af16-42b2-8111-7f202c979e28')
    ->execute();

  foreach ($nids as $nid) {
    $node = Node::load($nid);
    if ($node->field_leadership->value == '1') {
      $node->field_staff_group->set(0, current($leader_id));
    }
    else {
      $node->field_staff_group->set(0, current($staff_id));
    }
    // Figure out what the status is and set the content moderation
    // appropriately.
    if ($node->status->value == 0) {
      $node->moderation_state->set(0, 'archived');
    }
    $node->save();
    $sandbox['current']++;
  }

  \Drupal::messenger()
    ->addMessage($sandbox['current'] . ' persons processed.');

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }
}
