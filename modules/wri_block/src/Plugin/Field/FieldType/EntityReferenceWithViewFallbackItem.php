<?php

namespace Drupal\wri_block\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Defines the 'wri_block_entity_reference_with_view_fallback' field type.
 *
 * @FieldType(
 *   id = "wri_block_entity_reference_with_view_fallback",
 *   label = @Translation("Entity Reference with View Fallback"),
 *   category = @Translation("General"),
 *   default_widget = "entity_reference_autocomplete",
 *   default_formatter = "related_field_formatter"
 * )
 *
 * @DCG
 */
class EntityReferenceWithViewFallbackItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }
}
