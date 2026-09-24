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
      ->accessCheck(FALSE)
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
    ->accessCheck(FALSE)
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
    ->accessCheck(FALSE)
    ->condition('uuid', '51a8936a-2d62-4452-b5c2-1fd0b542e39e')
    ->execute();

  $staff_id = \Drupal::entityQuery('taxonomy_term')
    ->accessCheck(FALSE)
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
    if ($node->getStatus()->value == 0) {
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

/**
 * Populates Person Region, Tags, and Areas of Expertise from their Programs.
 */
function wri_person_post_update_populate_program_expertise(&$sandbox) {
  $storage = \Drupal::entityTypeManager()->getStorage('node');

  if (!isset($sandbox['total'])) {
    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'person')
      ->exists('field_project_expert')
      ->execute();
    $sandbox['nids'] = array_values($nids);
    $sandbox['total'] = count($sandbox['nids']);
    $sandbox['current'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $batch_size = 25;
  $nids = array_slice($sandbox['nids'], $sandbox['current'], $batch_size);
  $persons = $storage->loadMultiple($nids);

  // Cache loaded programs across batches, since many persons share them.
  static $programs_cache = [];

  foreach ($persons as $person) {
    $program_ids = array_column($person->get('field_project_expert')->getValue(), 'target_id');
    if (empty($program_ids)) {
      $sandbox['current']++;
      continue;
    }

    $regions = array_column($person->get('field_region')->getValue(), 'target_id');
    $tags = array_column($person->get('field_tags')->getValue(), 'target_id');
    $expertise = array_column($person->get('field_areas_of_expertise')->getValue(), 'target_id');

    $to_load = array_diff($program_ids, array_keys($programs_cache));
    if ($to_load) {
      $programs_cache += $storage->loadMultiple($to_load);
    }

    foreach ($program_ids as $program_id) {
      $program = $programs_cache[$program_id] ?? NULL;
      if (!$program || $program->bundle() != 'project_detail') {
        continue;
      }
      $regions = array_merge($regions, array_column($program->get('field_region')->getValue(), 'target_id'));
      $tags = array_merge($tags, array_column($program->get('field_tags')->getValue(), 'target_id'));
      $primary_topic = $program->get('field_primary_topic')->target_id;
      if (!empty($primary_topic)) {
        $expertise[] = $primary_topic;
      }
    }

    $person->set('field_region', array_values(array_unique($regions)));
    $person->set('field_tags', array_values(array_unique($tags)));
    $person->set('field_areas_of_expertise', array_values(array_unique($expertise)));
    $person->save();

    $sandbox['current']++;
  }

  \Drupal::messenger()
    ->addMessage($sandbox['current'] . ' of ' . $sandbox['total'] . ' persons processed.');

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }
}
