<?php

namespace Drupal\wri_search\Plugin\Field\FieldType;

/**
 * Variant of the 'text' field that calculates the related animal.
 *
 * @FieldType(
 *   id = "smart_topic_parent",
 *   label = @Translation("Smart Topic parent"),
 *   description = @Translation("Any topic term attached to content, including its parent or itself."),
 * )
 */
class WriSmartTopic extends WriCalculatedField {

  /**
   * Calculates the value of the field and sets it.
   */
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      $entity = $this->getEntity();
      if (!$entity->isNew()) {
        $parent = $this->getAllTopics();
        $this->parent->setValue($parent);
      }
      $this->isCalculated = TRUE;
    }
  }

  /**
   * Returns the parent id, or the term id if no parent set.
   */
  private function getAllTopics() {
    // Add field_primary_topic.
    // Add field_topics
    // Add field_tags
    // Add field_areas_of_expertise
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
