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
      ->condition('u.field_narrative_taxonomy_value', '%<a href="/node/[node:%', 'LIKE')
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
  $start_value = $sandbox['current'];
  $nids = Drupal::database()->select('node__field_narrative_taxonomy', 'u')
    ->condition('u.field_narrative_taxonomy_value', '%<a href="/node/[node:%', 'LIKE')
    ->condition('entity_id', $sandbox['current'], '>=')
    ->fields('u')
    ->orderBy('entity_id')
    ->range(0, $users_per_batch)
    ->execute();

  if (empty($nids)) {
    $sandbox['#finished'] = 1;
    return;
  }
  // Loop through each node.
  foreach ($nids as $result) {
    $node = Node::load($result->entity_id);
    if ($node->hasTranslation($result->langcode)) {
      $node = $node->getTranslation($result->langcode);
    }
    $taxonomy_value = $node->field_narrative_taxonomy->getValue();
    // Replace link strings with new values.
    $taxonomy_value[0]['value'] = str_replace(
      ['<a href="/node/[node:field_projects:target_id]">[node:field_projects:entity]</a>',
        '<a href="/node/[node:field_primary_contacts:target_id]">[node:field_primary_contacts:entity]</a>',
        '<a href="/node/[node:field_featured_experts:target_id]">[node:field_featured_experts:entity]</a>',
      ],
      ['[node:field_projects:entity:link]',
        '[node:field_primary_contacts:entity:link]',
        '[node:field_featured_experts:entity:link]',
      ],
      $taxonomy_value[0]['value']
    );
    $node->field_narrative_taxonomy->setValue($taxonomy_value);

    // Save the node.
    $node->save();
    $sandbox['current'] = $result->entity_id;
  }

  \Drupal::messenger()
    ->addMessage($sandbox['current'] . ' node last processed.');

  $sandbox['#finished'] = ($sandbox['current'] == $start_value);
}
