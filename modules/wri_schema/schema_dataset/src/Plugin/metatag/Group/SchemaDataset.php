<?php

namespace Drupal\schema_dataset\Plugin\metatag\Group;

use Drupal\schema_web_page\Plugin\metatag\Group\SchemaWebPage;

/**
 * Provides a plugin for the 'WebPage' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_dataset",
 *   label = @Translation("Schema.org: Dataset"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>.", arguments = {
 *     ":url" = "https://schema.org/Dataset",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaDataset extends SchemaWebPage {
  // Nothing here yet. Just a placeholder class for a plugin.
}
