<?php

namespace Drupal\schema_dataset\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_dataset_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of dataset."),
 *   name = "@type",
 *   group = "schema_dataset",
 *   weight = -11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SchemaDatasetType extends SchemaNameBase {

  /**
   * {@inheritdoc}
   */
  public static function labels() {
    return [
      'Dataset',
    ];
  }

}
