<?php

namespace Drupal\wri_author\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'list_of_authors' formatter.
 *
 * @FieldFormatter(
 *   id = "list_of_authors",
 *   label = @Translation("List of authors"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ListOfAuthors extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $build = [];
    $cleaned_authors = [];

    foreach ($elements as $item) {
      if (trim(strip_tags(render($item)))) {
        $cleaned_authors[] = trim(render($item));
      }
    }
    if (count($cleaned_authors) > 1) {
      $last_author = array_pop($cleaned_authors);
    }

    $string = implode(', ', $cleaned_authors);

    if (isset($last_author)) {
      $string .= ' ' . $this->t('and') . ' ' . $last_author;
    }

    if (!empty($string)) {
      $build[0] = [
        '#markup' => $string,
      ];
    }

    return $build;
  }

}
