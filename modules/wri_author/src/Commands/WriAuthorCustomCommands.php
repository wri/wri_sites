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
   * Drush command that consolidates authors by type.
   *
   * @param string $type
   *   Author type - start with internal.
   * @param int $number
   *   Number of Authors to process.
   *
   * @command wri_author:merge-authors-by-type
   * @usage wri_author:merge-authors-by-type internal 50
   */
  public function mergeAuthorsByType($type = 'internal', $number = 5) {
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
   * Drush command that to consolidate authors to internal.
   *
   * @param int $number
   *   Number of Authors to process.
   *
   * @command wri_author:merge-authors
   * @usage wri_author:merge-authors
   */
  public function mergeAuthors($number = 50) {
    // Find any authors that have the same name, within bundle.
    // SELECT name FROM wri_author_field_data GROUP BY name HAVING count(name)>1
    $author_list = \Drupal::entityQueryAggregate('wri_author')
      ->groupBy('name')
      ->conditionAggregate('name', 'COUNT', '1', '>')
      ->range(0, $number)
      ->execute();

    // Get duplicate author IDs by type.
    foreach ($author_list as $author) {
      if ($author['name']) {
        $duplicate_authors = \Drupal::entityQuery('wri_author')
          ->condition('name', $author['name'])
          ->execute();
      }
      // Prefer the internal author:
      if ($duplicate_authors) {
        if (2 == count($duplicate_authors)) {
          $duplicate_authors = array_values($duplicate_authors);
          $author_one = WRIAuthor::load($duplicate_authors[0]);
          $author_two = WRIAuthor::load($duplicate_authors[1]);
          if ('internal' == $author_two->bundle()) {
            $internal_author_id = $duplicate_authors[1];
          }
          else {
            $internal_author_id = $duplicate_authors[0];
          }
        }
        else {
          return;
        }

        foreach ($duplicate_authors as $author_id) {
          // Load all the nodes referencing the external id.
          $author_nodes = \Drupal::entityQuery('node')
            ->condition('field_authors', $author_id, 'IN')
            ->execute();

          // Switch that external id out with the internal id.
          if ($author_nodes && ($author_id !== $internal_author_id)) {
            foreach ($author_nodes as $node_id) {
              $node = Node::load($node_id);
              $author_list = $node->get('field_authors')->getValue();
              $key = array_search($author_id, array_column($author_list, 'target_id'));
              $node->get('field_authors')->removeItem($key);
              $node->field_authors[] = ['target_id' => $internal_author_id];
              $node->save();
              echo 'Internal Author: ' . $internal_author_id . '
';
              echo 'Duplicate: ' . $author_id . '
';
              echo 'Node Updated: ' . $node_id . '
';
            }
          }
          // Delete duplicate author.
          $excess_author = WRIAuthor::load($author_id);
          if ($author_id !== $internal_author_id) {
            echo 'Duplicate Deleted: ' . $author_id . '

';
            $excess_author->delete();
          }
        }
      }
    }
  }

  /**
   * Drush command to delete broken author references.
   *
   * @command wri_author:delete-missing-references
   * @usage wri_author:delete-missing-references
   */
  public function deleteMissingReferences() {
    // Find any field_author values that reference non-existent authors.
    $author_list = \Drupal::entityQueryAggregate('wri_author')
      ->groupBy('name')
      ->conditionAggregate('name', 'COUNT', '1', '>')
      ->execute();

    // Get author IDs without a name.
    foreach ($author_list as $author) {
      if (!$author['name']) {
        $empty_authors = \Drupal::entityQuery('wri_author')
          ->condition('name', $author['name'])
          ->range(0, 50)
          ->execute();
      }
      // Remove authors from nodes/delete the author.
      if ($empty_authors) {
        foreach ($empty_authors as $author_id) {
          // Load all the nodes referencing the id.
          $author_nodes = \Drupal::entityQuery('node')
            ->condition('field_authors', $author_id, 'IN')
            ->execute();

          // Remove the broken author ID from nodes.
          if ($author_nodes) {
            foreach ($author_nodes as $node_id) {
              $node = Node::load($node_id);
              $author_list = $node->get('field_authors')->getValue();
              $key = array_search($author_id, array_column($author_list, 'target_id'));
              $node->get('field_authors')->removeItem($key);
              $node->save();
              echo 'Empty Author ID: ' . $author_id . '
';
              echo 'Node Updated: ' . $node_id . '
';
            }
          }
          // Delete broken author.
          $empty_author = WRIAuthor::load($author_id);
          echo 'Empty Author Deleted: ' . $author_id . '

';
          $empty_author->delete();
        }
      }
    }
  }

}
