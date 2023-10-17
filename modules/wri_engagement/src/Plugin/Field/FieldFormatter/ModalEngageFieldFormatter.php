<?php

namespace Drupal\wri_engagement\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'modal_engage_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "modal_engage_field_formatter",
 *   label = @Translation("Modal/Download button"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ModalEngageFieldFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $output = parent::view($items, $langcode);

    if (isset($output['#items'])) {

      $build['#theme'] = 'download_modal';
      $build['#content'] = $output;
    }
    else {
      $build = $output;
    }
    return $build;
  }

}
