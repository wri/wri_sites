<?php

namespace Drupal\wri_event\Plugin\DsField;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the terms from a chosen taxonomy vocabulary.
 *
 * @DsField(
 *   id = "addtocal",
 *   title = @Translation("Add To Calendar button"),
 *   entity_type = "node",
 *   ui_limit = {"event|*"}
 * )
 */
class CalendarEvent extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->configuration['entity'];
    $info = [];

    $start = '';
    if (isset($entity->field_date_time->value)) {
      $start = $entity->field_date_time->value;
      $start_date = DrupalDateTime::createFromTimestamp($start);
    }

    $end = '';
    if (isset($entity->field_date_time->end_value)) {
      $end = $entity->field_date_time->end_value;
      $end_date = DrupalDateTime::createFromTimestamp($end);
    }

    $today = DrupalDateTime::createFromTimestamp(time());

    // If this event is in the future build button.
    if (!$today->diff($end_date)->invert) {
      $location = '';
      if (isset($entity->field_location->value)) {
        $location = $entity->field_location->value;
      }
      $title = $this->t('Add to Calendar');
      $info['#markup'] = '<div class="event-subscribe-wrapper"><a href="#" title="' . $title . '" class="addeventatc">' . $title;
      $info['#markup'] .= '<span class="_start">' . $start_date->format('m/d/Y g:i a') . '</span>';
      $info['#markup'] .= '<span class="_end">' . $end_date->format('m/d/Y g:i a') . '</span>';
      $info['#markup'] .= '<span class="_zonecode">15</span>';
      $info['#markup'] .= '<span class="_location">' . $location . '</span>';
      $info['#markup'] .= '<span class="_summary">' . $entity->getTitle() . '</span>';
      $info['#markup'] .= '<span class="_description">' . $this->t('%title - from WRI: %url', [
        '%title' => $entity->getTitle(),
        '%url' => 'http://www.wri.org/node/' . $entity->id(),
      ]) . '</span>';
      $info['#markup'] .= '</a></div>';
      $info['#attached']['library'][] = 'wri_event/event';
    }

    return $info;
  }

}
