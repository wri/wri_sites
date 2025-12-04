<?php

namespace Drupal\wri_event\Plugin\DsField;

use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders Past Event if the event is over.
 *
 * @DsField(
 *   id = "has_webinar",
 *   title = @Translation("Shows text about a webinar, if there is one."),
 *   entity_type = "node",
 *   ui_limit = {"event|*"}
 * )
 */
class HasWebinar extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->configuration['entity'];
    $title = '';
    $info = [];

    if (isset($entity->field_date_time->end_value)) {
      $end = $entity->field_date_time->end_value;
    }

    if (isset($entity->field_body_contains_recording->value)) {
      $has_webinar = $entity->field_body_contains_recording->value;
    }

    // If this event is in the past, show that.
    if ($has_webinar) {
      $title = $this->t('Event Recording');
    } elseif ($end > time()) {
      $title = $this->t('Upcoming Event');
    }

    if (!empty($title)) {
      $info['#markup'] = '<span class="event-info-item event-info-item-past">' . $title . '</span>';
    }
    return $info;
  }

}
