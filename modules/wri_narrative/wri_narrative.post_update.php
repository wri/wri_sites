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
      ['[node:projects_links]',
        '[node:primary_contact_links]',
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

/**
 * Updates the tokens in narrative taxonomies, round 2.
 */
function wri_narrative_post_update_rewrite_narrative_taxonomies1(&$sandbox) {
  // We don't display the narrative taxonomy on nodes older than 2020, so clear
  // that boilerplate out of the database.
  Drupal::database()->query("DELETE FROM node__field_narrative_taxonomy WHERE entity_id IN (SELECT nid FROM node_field_data WHERE created<1577836800 AND type IN ('data', 'publication'))")->execute();
  Drupal::database()->query("DELETE FROM node_revision__field_narrative_taxonomy WHERE entity_id IN (SELECT nid FROM node_field_data WHERE created<1577836800 AND type IN ('data', 'publication'))")->execute();

  if (!isset($sandbox['total'])) {
    $sandbox['total'] = Drupal::database()->select('node__field_narrative_taxonomy', 'u')
      ->condition('u.field_narrative_taxonomy_value', '[node:field_(projects|primary_contacts):entity:link]', 'REGEXP')
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
    ->condition('u.field_narrative_taxonomy_value', '\[node:field_(projects|primary_contacts):entity:link\]', 'REGEXP')
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
    $within_phrase = \Drupal::config('wri_node.settings')->get('within_phrase');
    $within_phrase = str_replace('[node:projects_links_within]', '[node:field_projects:entity:link]', $within_phrase);
    // Replace link strings with new values.
    $taxonomy_value[0]['value'] = str_replace(
      ['[node:field_primary_contacts:entity:link]',
        $within_phrase . ' ',
      ],
      ['[node:primary_contact_links]',
        '[node:projects_links_within]',
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

/**
 * Updates field config to use org_name token in narrative taxonomies, round 3.
 */
function wri_narrative_post_update_rewrite_narrative_taxonomies2(&$sandbox) {
  $database = \Drupal::database();

  if (!isset($sandbox['total'])) {
    $sandbox['total'] = $database->select('node__field_narrative_taxonomy', 'f')
      ->fields('f', ['entity_id'])
      ->countQuery()
      ->execute()
      ->fetchField();
    $sandbox['current'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $batch_size = 25;
  $query = $database->select('node__field_narrative_taxonomy', 'f')
    ->fields('f', ['entity_id', 'langcode'])
    ->condition('f.entity_id', $sandbox['current'], '>=')
    ->orderBy('f.entity_id')
    ->range(0, $batch_size)
    ->distinct();

  $results = $query->execute()->fetchAll();

  if (empty($results)) {
    $sandbox['#finished'] = 1;
    return;
  }

  $replacements = [
    "WRI's" => "[wri_tokens:org_name]'s",
    "WRI&#39;s" => "[wri_tokens:org_name]&#39;s",
  ];

  foreach ($results as $record) {
    $node = \Drupal\node\Entity\Node::load($record->entity_id);
    if (!$node) {
      continue;
    }

    // Load the correct translation.
    if ($node->hasTranslation($record->langcode)) {
      $node = $node->getTranslation($record->langcode);
    }

    if (!$node->hasField('field_narrative_taxonomy') || $node->get('field_narrative_taxonomy')->isEmpty()) {
      continue;
    }

    $value = $node->get('field_narrative_taxonomy')->value;
    $new_value = strtr($value, $replacements);

    if ($new_value !== $value) {
      $node->get('field_narrative_taxonomy')->value = $new_value;
      $node->save();
    }

    $sandbox['current'] = $record->entity_id;
  }

  $sandbox['#finished'] = FALSE;
  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
  }
}
