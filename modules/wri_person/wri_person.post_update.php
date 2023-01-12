<?php

/**
 * @file
 * Person module post_update implementations.
 */

function wri_person_post_update_person_grouping(&$sandbox) {
  // Fill sandbox.
  // Work through Persons.
  if (!isset($sandbox['total'])) {
    $nids = \Drupal::entityQuery('node')
      ->condition('bundle', 'person')
      ->condition('field_staff_group', '', 'IS NULL')
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
    ->condition('bundle', 'person')
    ->condition('field_staff_group', '', 'IS NULL')
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
    $node = \Drupal\node\Entity\Node::load($nid);
    // ... do something with the loaded node entity ...
    if ($node->field_leadership->value == '1') {
      $node->field_staff_group->set($leader_id);
    }
    else {
      $node->field_staff_group->set($staff_id);
    }
    $node->save();
    $sandbox['current']++;
  }

  \Drupal::messenger()
    ->addMessage($sandbox['current'] . ' users processed.');

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }
}
