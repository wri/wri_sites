<?php

namespace Drupal\wri_media\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'wri_media_custom_responsive_image_widget' field widget.
 *
 * @FieldWidget(
 *   id = "wri_media_custom_responsive_image_widget",
 *   label = @Translation("Custom Responsive Image Widget"),
 *   field_types = {
 *     "wri_media_custom_responsive_image"
 *   }
 * )
 */
class CustomResponsiveImageWidget extends WidgetBase {

  /**
   * {@inheritDoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];

    $element['target_id_sm'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => $this->t('Image for Small breakpoint'),
      '#default_value' => $item->target_id_sm ?? NULL,
      '#limit' => 1,
      '#weight' => '0',
    ];

    $element['target_id_md'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Image for Medium breakpoint'),
      '#allowed_bundles' => ['image'],
      '#weight' => '0',
      '#limit' => 1,
      '#default_value' => $item->target_id_md ?? NULL,
    ];

    $element['target_id_lg'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Image for Large breakpoint'),
      '#allowed_bundles' => ['image'],
      '#default_value' => $item->target_id_lg ?? NULL,
      '#weight' => '0',
      '#limit' => 1,
    ];

    $element['target_id_xl'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Image for XL breakpoint'),
      '#allowed_bundles' => ['image'],
      '#default_value' => $item->target_id_xl ?? NULL,
      '#weight' => '0',
      '#limit' => 1,
    ];

    return $element;
  }

}
