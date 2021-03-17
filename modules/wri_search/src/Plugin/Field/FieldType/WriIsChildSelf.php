<?php

namespace Drupal\wri_search\Plugin\Field\FieldType;

/**
 * Variant of the 'text' field that calculates the related animal.
 *
 * @FieldType(
 *   id = "term_child_of_parent",
 *   label = @Translation("Is Child"),
 *   description = @Translation("Returns the term if it's a child, otherwise 0."),
 * )
 */
class WriIsChildSelf extends WriCalculatedField {

  /**
   * Calculates the value of the field and sets it.
   */
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      $entity = $this->getEntity();
      if (!$entity->isNew()) {
        $is_child = $this->getIsChild();
        $this->parent->setValue($is_child);
      }
      $this->isCalculated = TRUE;
    }
  }

  /**
   * Returns the term ID if it's a child, otherwise 0.
   */
  private function getIsChild() {
    $entity = $this->getEntity();
    if ($entity->parent->target_id == 0) {
      $parent = 0;
    }
    else {
      $parent = $entity->id();
    }

    return $parent;
  }

}
