<?php

namespace Drupal\wri_looking_terms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plain text formatter for formatted long text fields.
 *
 * Renders the field's processed value (after its text format, including
 * token replacement, has run) with all HTML tags stripped.
 *
 * @FieldFormatter(
 *   id = "wri_looking_terms_plain_text",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "text_long"
 *   }
 * )
 */
class PlainTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $item->processed,
        '#allowed_tags' => [],
      ];
    }
    return $elements;
  }

}
