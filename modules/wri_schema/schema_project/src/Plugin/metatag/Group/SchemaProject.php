<?php

namespace Drupal\schema_project\Plugin\metatag\Group;

use Drupal\schema_web_page\Plugin\metatag\Group\SchemaWebPage;

/**
 * Provides a plugin for the 'WebPage' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_project",
 *   label = @Translation("Schema.org: Project"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>.", arguments = {
 *     ":url" = "https://schema.org/Project",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaProject extends SchemaWebPage {
  // Nothing here yet. Just a placeholder class for a plugin.
}
