<?php

declare(strict_types=1);

namespace Drupal\wri_media\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\wri_search\Plugin\Field\FieldType\WriCalculatedField;

/**
 * Defines the 'wri_media_wri_media_insert_caption' field type.
 *
 * @FieldType(
 *   id = "wri_media_wri_media_insert_caption",
 *   label = @Translation("Caption placeholder"),
 *   description = @Translation("A field to hold a caption provided by parent entity."),
 *   default_formatter = "wri_media_caption_placeholder",
 * )
 */
final class WriMediaInsertCaptionItem extends WriCalculatedField {

  /**
   * Whether the value has been calculated.
   *
   * @var bool
   */
  protected $isCalculated = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    $this->ensureCalculated();
    return parent::__get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureCalculated();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->ensureCalculated();
    return parent::getValue();
  }

  /**
   * Calculates the value of the field and sets it.
   */
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      $entity = $this->getEntity();
      if (isset($entity->caption_value)) {
        $this->parent->setValue($entity->caption_value);
      }
      $this->isCalculated = TRUE;
    }
  }


}
