<?php

/**
 * @file
 * Program module post_update implementations.
 */

use Drupal\layout_builder\SectionComponent;
use Drupal\node\Entity\Node;

/**
 * Post-update: Put Share List at the top of section[1]; remove Twitter block.
 *
 * - Ensures `field_social_sharing_links` is in the same region as
 *   `field_intro_paragraphs` and is sorted to the top of that region.
 * - Removes any `field_twitter_account` field blocks.
 */
function wri_program_post_update_sharelist_top(&$sandbox) {
  // Get Program nodes that have a Layout Builder value.
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'program_center')
    ->exists('layout_builder__layout')
    ->accessCheck(FALSE)
    ->execute();

  if (!$nids) {
    $sandbox['#finished'] = 1;
    return t('No Program nodes with Layout Builder overrides found.');
  }

  // Pull formatter settings from the "full" display if available.
  $display = \Drupal::entityTypeManager()
    ->getStorage('entity_view_display')
    ->load('node.program_center.full');
  $display_comp = $display ? $display->getComponent('field_social_sharing_links') : NULL;
  $formatter = [
    'type' => is_array($display_comp) && isset($display_comp['type']) ? $display_comp['type'] : 'entity_reference_label',
    'label' => is_array($display_comp) && isset($display_comp['label']) ? $display_comp['label'] : 'hidden',
    'settings' => is_array($display_comp) && isset($display_comp['settings']) ? $display_comp['settings'] : [],
  ];

  $uuid_service = \Drupal::service('uuid');
  $changed_count = 0;

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

    $section = $sections[1];
    $components = $section->getComponents();
    if (empty($components)) {
      continue;
    }

    $changed = FALSE;

    // Track the region, whether Share exists, and Twitter uuids.
    $paragraphs_region = NULL;
    $share_uuid = NULL;
    $twitter_uuids = [];

    foreach ($components as $uuid => $component) {
      $config = $component->get('configuration') ?? [];
      $plugin_id = $config['id'] ?? '';

      if ($plugin_id === 'field_block:node:program_center:field_intro_paragraphs') {
        $paragraphs_region = $component->getRegion();
      }
      elseif ($plugin_id === 'field_block:node:program_center:field_social_sharing_links') {
        $share_uuid = $uuid;
      }
      elseif ($plugin_id === 'field_block:node:program_center:field_twitter_account') {
        $twitter_uuids[] = $uuid;
      }
    }

    // Remove any Twitter field blocks.
    if ($twitter_uuids) {
      foreach ($twitter_uuids as $tuuid) {
        if ($section->getComponent($tuuid)) {
          $section->removeComponent($tuuid);
          $changed = TRUE;
        }
      }
    }

    // If Paragraphs exists, ensure Share is at the top of that region.
    if ($paragraphs_region) {
      $very_low_weight = -1000000;

      if ($share_uuid) {
        $share = $section->getComponent($share_uuid);
        if ($share) {
          if ($share->getRegion() !== $paragraphs_region) {
            $share->setRegion($paragraphs_region);
            $changed = TRUE;
          }
          if ((int) $share->getWeight() !== $very_low_weight) {
            $share->setWeight($very_low_weight);
            $changed = TRUE;
          }
        }
      }
      else {
        // Create Share in the Paragraphs region with a very low weight.
        $cfg = [
          'id' => 'field_block:node:program_center:field_social_sharing_links',
          'provider' => 'layout_builder',
          'formatter' => $formatter,
          'context_mapping' => ['entity' => 'layout_builder.entity'],
          'label' => 'Share List',
          'label_display' => '0',
          'third_party_settings' => [],
        ];
        $new_component = new SectionComponent($uuid_service->generate(), $paragraphs_region, $cfg);
        $new_component->setWeight($very_low_weight);
        $section->appendComponent($new_component);
        $changed = TRUE;
      }

      // Normalize weights in this region so Share renders first.
      if ($changed) {
        $rows = [];
        foreach ($section->getComponents() as $c_uuid => $c) {
          if ($c->getRegion() !== $paragraphs_region) {
            continue;
          }
          $id = ($c->get('configuration')['id'] ?? '');
          $rows[] = [
            'uuid' => $c_uuid,
            'id'   => $id,
            'is_share' => $id === 'field_block:node:program_center:field_social_sharing_links',
            'w' => (int) $c->getWeight(),
          ];
        }
        usort($rows, static function ($a, $b) {
          if ($a['is_share'] && !$b['is_share']) {
            return -1;
          }
          if ($b['is_share'] && !$a['is_share']) {
            return 1;
          }
          if ($a['w'] === $b['w']) {
            return strcmp($a['id'], $b['id']);
          }
          return $a['w'] <=> $b['w'];
        });
        $w = 0;
        foreach ($rows as $r) {
          $section->getComponent($r['uuid'])->setWeight($w);
          $w += 10;
        }
      }
    }

    if ($changed) {
      $sections[1] = $section;
      $node->set('layout_builder__layout', $sections);
      $node->setNewRevision(FALSE);
      $node->save();
      $changed_count++;
    }
  }

  $sandbox['#finished'] = 1;
  return \Drupal::translation()->formatPlural(
    $changed_count,
    'Updated @count Program node.',
    'Updated @count Program nodes.'
  );
}
