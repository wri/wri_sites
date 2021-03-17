<?php

namespace Drupal\wri_author\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commands for cleaning up wri_author references.
 */
class WriAuthorCustomCommands extends DrushCommands {

  /**
   * Drush command that displays the given text.
   *
   * @command wri_author:merge-authors
   * @usage wri_author:merge-authors
   */
  public function mergeAuthors() {
    // Find any authors that have the same name, within bundle.
    // SELECT name FROM wri_author_field_data GROUP BY name HAVING count(name)>1
    // Set all references to that author to the first result.
    // Delete all other results.
  }

  /**
   * Drush command that deleted broken references.
   *
   * @command wri_author:delete-missing-references
   * @usage wri_author:delete-missing-references
   */
  public function deleteMissingReferences() {
    // Find any field_author values that reference non-existent authors.
    // Load the node
    // Remove the bad references.
    // Save the node.
  }

}
