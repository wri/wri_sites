<?php

namespace Drupal\wri_search\Plugin\Field\FieldType;

/**
 * Topic field that includes all fields that could contain the topic.
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
        $is_child = $this->computeValue();
        $this->parent->setValue($is_child);
      }
      $this->isCalculated = TRUE;
    }
  }

  /**
   * Compute the values.
   */
  protected function computeValue() {
    // Add field_primary_topic.
    // Add field_topics
    // Add field_tags
    // Add field_areas_of_expertise.
    $node = $this->getEntity();
    $field_primary_topics = $node->field_primary_topic ? $node->field_primary_topic->referencedEntities() : [];
    $field_topics = $node->field_topics ? $node->field_topics->referencedEntities() : [];
    $field_tags = $node->field_tags ? $node->field_tags->referencedEntities() : [];
    $field_areas_of_expertise = $node->field_areas_of_expertise ? $node->field_areas_of_expertise->referencedEntities() : [];

    $all_tags = $field_primary_topics + $field_topics + $field_tags + $field_areas_of_expertise;
    $index = 0;
    $results = [];
    foreach ($all_tags as $tag) {
      if ($tag->bundle() == 'topics_and_subtopics') {
        if ($tag->parent->target_id == 0) {
          $results[$index] = $tag->id();
        }
        else {
          $results[$index] = $tag->parent->target_id;
        }
        $index++;
      }
    }

    return $results;
  }

}
