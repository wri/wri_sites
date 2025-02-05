<?php

declare(strict_types=1);

namespace Drupal\wri_event\Plugin\Field\FieldFormatter;

use Drupal\addtocal\Plugin\Field\FieldFormatter\AddtocalView;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'wri_event_add_to_cal' formatter.
 *
 * @FieldFormatter(
 *   id = "wri_event_wri_event_add_to_cal",
 *   label = @Translation("wri_event_add_to_cal"),
 *   field_types = {"addtocal"},
 * )
 */
final class WriEventAddToCalFormatter extends AddtocalView {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    $field = $this->fieldDefinition;
    $token_data = [
      $field->getTargetEntityTypeId() => $entity,
    ];
    $timezone_override = $this->getSetting('timezone_override') ?: NULL;
    $element = [];
    foreach ($items as $item) {
      $info = [];

      $start = '';
      if (isset($item->value)) {
        $start = $item->value;
        $start_date = DrupalDateTime::createFromTimestamp($start);
      }

      $end = '';
      if (isset($item->end_value)) {
        $end = $item->end_value;
        $end_date = DrupalDateTime::createFromTimestamp($end);
      }

      $today = DrupalDateTime::createFromTimestamp(time());

      // If this event is in the future build button.
      if (!$today->diff($end_date)->invert) {
        $title = $this->token->replace($this->getSetting('event_title'), $token_data, ['clear' => TRUE]) ?: $entity->label();
        $description = $this->token->replace($this->getSetting('description'), $token_data, ['clear' => TRUE]);
        $timezone = $item->timezone ? $item->timezone : $timezone_override;
        $location = '';
        if (isset($entity->field_location->value)) {
          $location = $entity->field_location->value;
        }
        $button_title = $this->t('Add to Calendar');
        $info['#markup'] = '<div class="event-subscribe-wrapper"><a href="#" title="' . $button_title . '" class="addeventatc">' . $button_title;
        $info['#markup'] .= '<span class="_start">' . $start_date->format('m/d/Y g:i a') . '</span>';
        $info['#markup'] .= '<span class="_end">' . $end_date->format('m/d/Y g:i a') . '</span>';
        $info['#markup'] .= '<span class="_zonecode">' . $timezone . '</span>';
        $info['#markup'] .= '<span class="_location">' . $location . '</span>';
        $info['#markup'] .= '<span class="_summary">' . $title . '</span>';
        $info['#markup'] .= '<span class="_description">' . $description . '</span>';
        $info['#markup'] .= '</a></div>';
        $info['#attached']['library'][] = 'wri_event/event';

        $element[] = $info;
      }
    }

    return $element;
  }

}
