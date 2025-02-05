<?php

declare(strict_types=1);

namespace Drupal\wri_event\Plugin\Field\FieldType;

use Drupal\smart_date\Plugin\Field\FieldType\SmartDateItem;

/**
 * Defines the 'wri_event_copy_to_cal' field type.
 *
 * @FieldType(
 *   id = "addtocal",
 *   label = @Translation("Add To Calendar button"),
 *   description = @Translation("Copies the value in the Date field."),
 *   default_formatter = "wri_event_wri_event_add_to_cal",
 * )
 */
final class CopyToCalItem extends SmartDateItem {

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
      if (isset($entity->field_date_time)) {
        $this->parent->setValue($entity->field_date_time->getValue());
      }
      $this->isCalculated = TRUE;
    }
  }

}
