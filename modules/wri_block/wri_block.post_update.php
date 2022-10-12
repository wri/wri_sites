<?php

/**
 * @file
 * Functions for wri custom blocks.
 */

/**
 * Converts related resource field blocks into a custom block type.
 */
function wri_block_post_update_empty_layout_fields(&$sandbox) {
  // Load all layouts with the :field_related_resources value.
  $nodes = Drupal::database()->select('node__layout_builder__layout', 'u')
    ->condition('u.layout_builder__layout_section', '%:field_related_resources%', 'LIKE')
    ->fields('u')
    ->execute();
  // Loop through each layout.
  foreach($nodes as $result) {
    $node = \Drupal\node\Entity\Node::load($result->entity_id);
    $layout = $node->get('layout_builder__layout');
    $sections = $layout->getSections();
    $components = $sections[0]->getComponents();

    foreach($components as $uuid => $component) {
      $configuration = $component->get('configuration');
      // Find the correct field.
      // Replace its configuration with ours.
      if ($configuration['formatter']['type'] == 'related_field_formatter') {
        $configuration['id'] = 'related_resources_fallback';
        $configuration['provider'] = 'wri_block';
        $configuration['context_mapping'] = [];
        $component->set('configuration', $configuration);
      }
    }

    $node->set('layout_builder__layout', $sections);
    $node->save();
  }
}
