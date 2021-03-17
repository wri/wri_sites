<?php

namespace Drupal\schema_publication_volume\Plugin\metatag\Group;

use Drupal\schema_web_page\Plugin\metatag\Group\SchemaWebPage;

/**
 * Provides a plugin for the 'WebPage' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_publication_volume",
 *   label = @Translation("Schema.org: PublicationVolume"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>.", arguments = {
 *     ":url" = "https://schema.org/PublicationVolume",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaPublicationVolume extends SchemaWebPage {
  // Nothing here yet. Just a placeholder class for a plugin.
}
