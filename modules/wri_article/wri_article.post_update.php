<?php

/**
 * @file
 * Article module post_update implementations.
 */

use Drupal\node\Entity\Node;

/**
 * Post-update: Ensure any Articles with customized layouts get the
 * new "Main content B" template.
 */
function wri_article_post_update_entity_main_view(&$sandbox) {
  // Get Article nodes that have a Layout Builder value.
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->exists('layout_builder__layout')
    ->accessCheck(FALSE)
    ->execute();

  if (!$nids) {
    $sandbox['#finished'] = 1;
    return t('No Article nodes with Layout Builder overrides found.');
  }


  /** @var \Drupal\node\NodeInterface[] $nodes */
  foreach (Node::loadMultiple($nids) as $node) {
    $layout = $node->get('layout_builder__layout');
    if ($layout->isEmpty()) {
      continue;
    }

    $sections = $layout->getSections();
    if (empty($sections) || !isset($sections[1])) {
      continue;
    }

    $share_component_added = FALSE;
    foreach ($sections as $section) {

      $components = $section->getComponents();
      if (empty($components)) {
        continue;
      }

      $changed = FALSE;

      foreach ($components as $uuid => $component) {
        $config = $component->get('configuration') ?? [];
        $plugin_id = $config['id'] ?? '';

        if ($plugin_id === 'entity_view:node' && $config['view_mode'] == 'main_content') {
          $config['view_mode'] = 'main_content_b';
          $component->set('configuration', $config);
          $changed = TRUE;
        }

      }

      if ($changed) {
        $node->set('layout_builder__layout', $sections);
        $node->setNewRevision(FALSE);
        $node->save();
        $changed_count++;
      }
    }
  }

  $sandbox['#finished'] = 1;
  return \Drupal::translation()->formatPlural(
    $changed_count,
    'Updated @count Article node.',
    'Updated @count Article nodes.'
  );
}
