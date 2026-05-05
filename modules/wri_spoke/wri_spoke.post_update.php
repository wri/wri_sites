<?php

/**
 * @file
 * Post update hooks for the spoke.
 */

/**
 * Exclude existing imported hub events from the XML sitemap.
 */
function wri_spoke_post_update_exclude_hub_events_from_sitemap(): void {
  if (!\Drupal::moduleHandler()->moduleExists('xmlsitemap')) {
    return;
  }

  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'event')
    ->condition('field_hub_canonical_url', '', '<>')
    ->accessCheck(FALSE)
    ->execute();

  if (empty($nids)) {
    return;
  }

  \Drupal::service('xmlsitemap.link_storage')->updateMultiple(
    ['status' => 0, 'status_override' => 1],
    ['type' => 'node', 'id' => array_values($nids)],
  );
}
