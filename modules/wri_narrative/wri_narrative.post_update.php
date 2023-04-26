<?php

/**
 * @file
 * Functions for wri custom blocks.
 */

use Drupal\node\Entity\Node;

/**
 * Updates the tokens in narrative taxonomies.
 */
function wri_narrative_post_update_rewrite_narrative_taxonomies(&$sandbox) {
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = Drupal::database()->select('node__field_narrative_taxonomy', 'u')
      ->condition('u.field_narrative_taxonomy_value', '%<a href="/node/%', 'LIKE')
      ->fields('u')
      ->countQuery()
      ->execute()
      ->fetchField();
    $sandbox['current'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $users_per_batch = 25;
  $nids = Drupal::database()->select('node__field_narrative_taxonomy', 'u')
    ->condition('u.field_narrative_taxonomy_value', '%<a href="/node/%', 'LIKE')
    ->fields('u')
    ->range(0, $users_per_batch)
    ->execute();

  if (empty($nids)) {
    $sandbox['#finished'] = 1;
    return;
  }
  // Loop through each node.
  foreach ($nids as $result) {
    $node = Node::load($result->entity_id);
    // Replace link strings with new values.
    $node->field_narrative_taxonomy->setValue(str_replace(
      ['<a href="/node/[node:field_projects:target_id]">[node:field_projects:entity]</a>',
        '<a href="/node/[node:field_primary_contacts:target_id]">[node:field_primary_contacts:entity]</a>'],
      ['[node:field_projects:entity:link]',
        '[node:field_primary_contacts:entity:link]'],
      $node->field_narrative_taxonomy->getValue()
    ));

    // Save the node.
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
