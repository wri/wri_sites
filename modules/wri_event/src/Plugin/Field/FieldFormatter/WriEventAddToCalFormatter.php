<?php

declare(strict_types=1);

namespace Drupal\wri_event\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'wri_event_add_to_cal' formatter.
 *
 * @FieldFormatter(
 *   id = "wri_event_wri_event_add_to_cal",
 *   label = @Translation("wri_event_add_to_cal"),
 *   field_types = {"wri_event_copy_to_cal"},
 * )
 */
final class WriEventAddToCalFormatter extends \Drupal\addtocal\Plugin\Field\FieldFormatter\AddtocalView {
}
