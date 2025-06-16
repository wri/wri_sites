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
  $config_factory = \Drupal::configFactory();
  $config_storage = \Drupal::service('config.storage');
  $prefix = 'field.field.node.';
  $suffix = '.field_narrative_taxonomy';

  $field_configs = array_filter(
    $config_storage->listAll($prefix),
    fn($name) => str_ends_with($name, $suffix)
  );

  $replacements = [
    "WRI's" => "[wri_tokens:org_name]'s",
    "WRI&#39;s" => "[wri_tokens:org_name]&#39;s",
  ];

  foreach ($field_configs as $config_name) {
    $config = $config_factory->getEditable($config_name);
    $changed = FALSE;

    // Replace in description.
    $description = $config->get('description') ?? '';
    $new_description = strtr($description, $replacements);
    if ($new_description !== $description) {
      $config->set('description', $new_description);
      $changed = TRUE;
    }

    // Replace in default_value.
    $default_value = $config->get('default_value');
    if (is_array($default_value) && isset($default_value[0]['value'])) {
      $text = $default_value[0]['value'];
      $new_text = strtr($text, $replacements);
      if ($new_text !== $text) {
        $default_value[0]['value'] = $new_text;
        $config->set('default_value', $default_value);
        $changed = TRUE;
      }
    }

    if ($changed) {
      $config->save();
      \Drupal::logger('wri_narrative')->notice('Updated field config: @name', ['@name' => $config_name]);
    }
  }
}
