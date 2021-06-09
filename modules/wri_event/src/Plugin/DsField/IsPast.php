<?php

namespace Drupal\wri_event\Plugin\DsField;

use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders Past Event if the event is over.
 *
 * @DsField(
 *   id = "is_past",
 *   title = @Translation("Shows whether an event is past."),
 *   entity_type = "node",
 *   ui_limit = {"event|*"}
 * )
 */
class IsPast extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->configuration['entity'];
    $info = [];

    if (isset($entity->field_date_time->end_value)) {
      $end = $entity->field_date_time->end_value;
    }

    // If this event is in the past, show that.
    if ($end < time()) {
      $title = $this->t('Past Event');
      $info['#markup'] = '<span class="event-info-item event-info-item-past">' . $title . '</span>';
    }

    return $info;
  }

}
