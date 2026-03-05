<?php

declare(strict_types=1);

namespace Drupal\wri_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Caption placeholder' formatter.
 *
 * @FieldFormatter(
 *   id = "wri_media_caption_placeholder",
 *   label = @Translation("Caption placeholder"),
 *   field_types = {"string"},
 * )
 */
final class CaptionPlaceholderFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $item->value,
      ];
    }
    return $element;
  }

}
