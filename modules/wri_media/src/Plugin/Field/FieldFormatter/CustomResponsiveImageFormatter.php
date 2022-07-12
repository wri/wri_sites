<?php

namespace Drupal\wri_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media\Entity\Media;

/**
 * Plugin implementation of the 'Custom Responsive Image Formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "wri_media_custom_responsive_image_formatter",
 *   label = @Translation("Custom Responsive Image Formatter"),
 *   field_types = {
 *     "wri_media_custom_responsive_image"
 *   }
 * )
 */
class CustomResponsiveImageFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $element['#theme'] = 'custom_responsive_image';

    $values = $items->first()->getValue();
    $default_media_id = $values['target_id_sm'];
    $default_media = Media::load($default_media_id);

    // Setup fallback image Large
    $element['image'] = [
      '#theme' => 'image',
      '#uri' => $default_media->field_media_image->first()->entity->getFileUri(),
    ];

    //$element['#image'] = 'test';

    return $element;
  }

}
