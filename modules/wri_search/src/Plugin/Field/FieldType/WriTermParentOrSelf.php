<?php

namespace Drupal\wri_search\Plugin\Field\FieldType;

/**
 * Variant of the 'text' field that calculates the related animal.
 *
 * @FieldType(
 *   id = "term_parent_or_self",
 *   label = @Translation("Parent or self"),
 *   description = @Translation("If the term has no value in its parent, show the term itself."),
 * )
 */
class WriTermParentOrSelf extends WriCalculatedField {

  /**
   * Calculates the value of the field and sets it.
   */
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      $entity = $this->getEntity();
      if (!$entity->isNew()) {
        $parent = $this->getTermParent();
        $this->parent->setValue($parent);
      }
      $this->isCalculated = TRUE;
    }
  }

  /**
   * Returns the parent id, or the term id if no parent set.
   */
  private function getTermParent() {
    $entity = $this->getEntity();
    if ($entity->parent->target_id == 0) {
      $parent = $entity->id();
    }
    else {
      $parent = $entity->parent->target_id;
    }

    return $parent;
  }

}
