<?php

/**
 * @file
 * Zoom module post_update implementations.
 */

/**
 * Transfer Orto integration settings to new configuration option.
 */
function wri_zoom_post_update_default_admin_settings() {
  $orto_url = \Drupal::config('content_snippets.content')->get('snippets')['orto_registration_url'];
  if (!empty($orto_url)) {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('wri_zoom.settings');
    $config->set('orto_enabled', TRUE);
    $config->set('orto_registration_url', $orto_url);
    $config->save(TRUE);
  }
}
