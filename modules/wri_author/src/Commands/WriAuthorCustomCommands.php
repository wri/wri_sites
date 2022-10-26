<?php

namespace Drupal\wri_author\Commands;

use Drush\Commands\DrushCommands;
use Drupal\wri_author\Entity\WRIAuthor;
use Drupal\node\Entity\Node;

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
  public function mergeAuthors($type = 'internal', $number = 5) {
    $i = 0;
    // Find any authors that have the same name, within bundle.
    // SELECT name FROM wri_author_field_data GROUP BY name HAVING count(name)>1
    $author_list = \Drupal::entityQueryAggregate('wri_author')
      ->groupBy('name')
      ->condition('type', $type)
      ->conditionAggregate('name', 'COUNT', '1', '>')
      ->range(0, $number)
      ->execute();

    // Get duplicate author IDs by type.
    foreach ($author_list as $author) {
      if ($author['name']) {
        $duplicate_authors = \Drupal::entityQuery('wri_author')
          ->condition('type', $type)
          ->condition('name', $author['name'])
          ->execute();
      }
      // Set all references to that author to the first result, i.e.:
      if ($duplicate_authors) {
        $first_author_id = array_shift(array_slice($duplicate_authors, 0, 1));

        foreach ($duplicate_authors as $author_id) {
          // Load all the nodes referencing the bad id.
          $author_nodes = \Drupal::entityQuery('node')
            ->condition('field_authors', $author_id, 'IN')
            ->execute();

          // Switch that bad id out with the good (first) id.
          if ($author_nodes && ($author_id !== $first_author_id)) {
            foreach ($author_nodes as $node_id) {
              $node = Node::load($node_id);
              $author_list = $node->get('field_authors')->getValue();
              $key = array_search($author_id, array_column($author_list, 'target_id'));
              $node->get('field_authors')->removeItem($key);
              $node->field_authors[] = ['target_id' => $first_author_id];
              $node->save();

              echo 'First Author: ' . $first_author_id . '
';
              echo 'Duplicate: ' . $author_id . '
';
              echo 'Node Updated: ' . $node_id . '
';
            }
          }
          // Delete duplicate author.
          $excess_author = WRIAuthor::load($author_id);
          if ($author_id !== $first_author_id) {
            echo 'Duplicate Deleted: ' . $author_id . '

';
            $excess_author->delete();
          }
        }
      }
    }
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
