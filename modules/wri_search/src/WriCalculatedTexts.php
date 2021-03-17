<?php

namespace Drupal\wri_search;

use Drupal\Core\Field\FieldItemList;

/**
 * Item list for a computed field that displays the current company.
 *
 * @see \Drupal\wri_search\Plugin\Field\FieldType\WriCalculatedField
 */
class WriCalculatedTexts extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->ensurePopulated();
    return new \ArrayIterator($this->list);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $this->ensurePopulated();
    return parent::getValue($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensurePopulated();
    return parent::isEmpty();
  }

  /**
   * Makes sure that the item list is never empty.
   *
   * For 'normal' fields that use database storage the field item list is
   * initially empty, but since this is a computed field this always has a
   * value.
   *
   * Make sure the item list is always populated, so this field is not skipped
   * for rendering in EntityViewDisplay and friends.
   *
   * @todo This will no longer be necessary once #2392845 is fixed.
   *
   * @see https://www.drupal.org/node/2392845
   */
  protected function ensurePopulated() {
    if (!isset($this->list[0])) {
      $this->list[0] = $this->createItem(0);
      // Populate the 0 value with the correct data.
      $this->list[0]->getValue();
    }
  }

}
