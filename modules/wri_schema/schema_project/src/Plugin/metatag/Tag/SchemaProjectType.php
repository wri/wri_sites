<?php

namespace Drupal\schema_project\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_project_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of project."),
 *   name = "@type",
 *   group = "schema_project",
 *   weight = -11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SchemaProjectType extends SchemaNameBase {

  /**
   * {@inheritdoc}
   */
  public static function labels() {
    return [
      'Project',
    ];
  }

}
