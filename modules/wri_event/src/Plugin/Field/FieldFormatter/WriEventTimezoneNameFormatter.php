<?php

declare(strict_types=1);

namespace Drupal\wri_event\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\smart_date\Plugin\Field\FieldFormatter\SmartDateDefaultFormatter;

/**
 * Smart Date formatter that shows full time zone names.
 *
 * Works like the Smart Date default formatter, but replaces the time zone
 * abbreviation that Smart Date formats output inside <span class="gmt">
 * (PHP "T", e.g. "EDT" or "+04") with the full time zone name, e.g.
 * "Eastern Daylight Time" or "Gulf Standard Time".
 *
 * @FieldFormatter(
 *   id = "wri_event_smartdate_timezone_name",
 *   label = @Translation("Smart Date | Full time zone name"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
final class WriEventTimezoneNameFormatter extends SmartDateDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode, $format = '') {
    $elements = parent::viewElements($items, $langcode, $format);
    $interface_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $timezone_override = $this->getSetting('timezone_override') ?: NULL;

    foreach ($elements as $delta => &$element) {
      if (!is_int($delta) || !isset($element['#value'], $element['#end_value'])) {
        continue;
      }
      $item = $items->get($delta);
      $timezone = ($item ? $item->timezone : NULL) ?: $timezone_override ?: date_default_timezone_get();
      $timestamps = [
        'start' => (int) $element['#value'],
        'end' => (int) $element['#end_value'],
      ];
      foreach ($timestamps as $part => $timestamp) {
        if (isset($element[$part]) && is_array($element[$part])) {
          $this->replaceTimezoneAbbreviation($element[$part], $timestamp, $timezone, $interface_langcode);
        }
        // The appended site time is shown in the viewer's time zone.
        if (isset($element['site_time'][$part]) && is_array($element['site_time'][$part])) {
          $this->replaceTimezoneAbbreviation($element['site_time'][$part], $timestamp, date_default_timezone_get(), $interface_langcode);
        }
      }
    }

    return $elements;
  }

  /**
   * Replaces time zone abbreviations in a render array with full names.
   *
   * @param array $element
   *   The render array to alter.
   * @param int $timestamp
   *   The timestamp the time zone name should be calculated for, so that
   *   daylight saving time is reflected correctly.
   * @param string $timezone
   *   The time zone identifier, e.g. "Asia/Dubai".
   * @param string $langcode
   *   The language code to localize the time zone name in.
   */
  protected function replaceTimezoneAbbreviation(array &$element, int $timestamp, string $timezone, string $langcode): void {
    if (isset($element['#markup']) && is_string($element['#markup']) && str_contains($element['#markup'], 'gmt')) {
      $formatter = new \IntlDateFormatter($langcode, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $timezone, NULL, 'zzzz');
      $name = $formatter->format($timestamp);
      if ($name) {
        $element['#markup'] = preg_replace('/(<span class=[\'"]gmt[\'"]>)\s*[^<\s][^<]*(<\/span>)/', '${1}' . Html::escape($name) . '${2}', $element['#markup']);
      }
    }
    foreach ($element as $key => &$child) {
      // Smart Date's time wrapper keeps the formatted output in "#text".
      if (is_array($child) && ($key === '#text' || !str_starts_with((string) $key, '#'))) {
        $this->replaceTimezoneAbbreviation($child, $timestamp, $timezone, $langcode);
      }
    }
  }

}
