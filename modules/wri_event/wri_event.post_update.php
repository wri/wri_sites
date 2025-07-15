<?php

/**
 * @file
 * Event module post_update implementations.
 */

use Drupal\node\Entity\Node;

/**
 * Adds a default Calendar Description for all future Events.
 */
function wri_event_post_update_calendar_description(&$sandbox) {
  // Fill sandbox.
  if (!isset($sandbox['total'])) {
    $sandbox['start_time'] = time();
    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'event')
      ->execute();
    $sandbox['total'] = count($nids);
    $sandbox['current'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $batch_size = 25;
  $nids = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', 'event')
    ->range(0, $batch_size)
    ->execute();
  if (empty($nids)) {
    $sandbox['#finished'] = 1;
    return;
  }

  foreach ($nids as $nid) {
    $event = Node::load($nid);
    if ($event->field_calendar_description->value == '') {
      $event->field_calendar_description->setValue([
        'value' => "[node:title] - from WRI: [node:url:absolute]",
        'format' => "basic_html",
      ]);
      $event->save();
    }
    $sandbox['current']++;
  }

  \Drupal::messenger()
    ->addMessage($sandbox['current'] . ' events processed.');

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }
}

/**
 * Add fields to facilitate event sharing from the hub.
 */
function wri_event_post_update_event_sharing_fields() {
  \Drupal::service('distro_helper.updates')->installConfig('field.storage.node.field_hub_canonical_url', 'wri_event', 'install', 'TRUE');
  \Drupal::service('distro_helper.updates')->installConfig('field.field.node.event.field_hub_canonical_url', 'wri_event', 'install', 'TRUE');
  \Drupal::service('distro_helper.updates')->updateConfig('core.entity_form_display.node.event.default',['content#field_hub_canonical_url', 'third_party_settings#field_group#group_publishing'], 'wri_event', 'install', 'TRUE');
}
