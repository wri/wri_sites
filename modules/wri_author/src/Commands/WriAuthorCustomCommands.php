<?php

namespace Drupal\wri_author\Commands;

use Drush\Commands\DrushCommands;
use Drupal\wri_author\Entity\WRIAuthor;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Drush commands for cleaning up wri_author references.
 */
class WriAuthorCustomCommands extends DrushCommands {

  /**
   * An instance of the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

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
    $query = $this->entityTypeManager->getStorage('wri_author')->getAggregateQuery();
    $author_list = $query->groupBy('name')
      ->condition('type', $type)
      ->conditionAggregate('name', 'COUNT', '1', '>')
      ->range(0, $number)
      ->execute();

    // Get duplicate author IDs by type.
    foreach ($author_list as $author) {
      if ($author['name']) {
        $query = $this->entityTypeManager->getStorage('wri_author');
        $duplicate_authors = $query->getQuery()
          ->condition('type', $type)
          ->condition('name', $author['name'])
          ->execute();
      }
      // Set all references to that author to the first result, i.e.:
      if ($duplicate_authors) {
        $primary_author_id = array_shift(array_slice($duplicate_authors, 0, 1));

        // Helper function to replace & remove duplicate authors.
        $this->replaceDuplicateAuthors($duplicate_authors, $primary_author_id);
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
    $query = $this->entityTypeManager->getStorage('wri_author')->getAggregateQuery();
    $author_list = $query->groupBy('name')
      ->conditionAggregate('name', 'COUNT', '1', '>')
      ->range(0, $number)
      ->execute();

    // Get duplicate author IDs by type.
    foreach ($author_list as $author) {
      if ($author['name']) {
        $query = $this->entityTypeManager->getStorage('wri_author');
        $duplicate_authors = $query->getQuery()
          ->condition('name', $author['name'])
          ->execute();
      }
      // Set the internal author as primary:
      if ($duplicate_authors) {
        if (2 == count($duplicate_authors)) {
          $duplicate_authors = array_values($duplicate_authors);
          $author_two = WRIAuthor::load($duplicate_authors[1]);
          if ('internal' == $author_two->bundle()) {
            $primary_author_id = $duplicate_authors[1];
          }
          else {
            $primary_author_id = $duplicate_authors[0];
          }
        }
        else {
          return;
        }

        // Helper function to replace & remove duplicate authors.
        $this->replaceDuplicateAuthors($duplicate_authors, $primary_author_id);
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
    $query = $this->entityTypeManager->getStorage('wri_author')->getAggregateQuery();
    $author_list = $query->groupBy('name')
      ->conditionAggregate('name', 'COUNT', '1', '>')
      ->execute();

    // Get author IDs without a name.
    foreach ($author_list as $author) {
      if (!$author['name']) {
        $query = $this->entityTypeManager->getStorage('wri_author');
        $empty_authors = $query->getQuery()
          ->condition('name', $author['name'])
          ->range(0, 50)
          ->execute();
      }
      // Remove authors from nodes/delete the author.
      if ($empty_authors) {
        foreach ($empty_authors as $author_id) {
          // Load all the nodes referencing the id.
          $query = $this->entityTypeManager->getStorage('node');
          $author_nodes = $query->getQuery()
            ->condition('field_authors', $author_id, 'IN')
            ->execute();

          // Remove the broken author ID from nodes.
          if ($author_nodes) {
            foreach ($author_nodes as $node_id) {
              $node_storage = $this->entityTypeManager->getStorage('node');
              $node = $node_storage->load($node_id);
              $author_list = $node->get('field_authors')->getValue();
              $key = array_search($author_id, array_column($author_list, 'target_id'));
              $node->get('field_authors')->removeItem($key);
              $node->save();
              echo "Empty Author ID: " . $author_id . "\n";
              echo "Node Updated: " . $node_id . "\n";
            }
            // Delete broken author.
            $empty_author = WRIAuthor::load($author_id);
            if (isset($empty_author)) {
              echo "Empty Author Deleted: " . $author_id . "\n\n";
              $empty_author->delete();
            }
          }
        }
      }
    }
  }

  /**
   * Replaces duplicate author IDs with a designated 'primary' ID.
   *
   * @param array $duplicate_authors
   *   The array of duplicate author IDs.
   * @param string $primary_author_id
   *   The Author ID designated as primary.
   */
  public function replaceDuplicateAuthors(array $duplicate_authors, $primary_author_id) {
    foreach ($duplicate_authors as $author_id) {
      // Load all the nodes referencing $author_id.
      $query = $this->entityTypeManager->getStorage('node');
      $author_nodes = $query->getQuery()
        ->condition('field_authors', $author_id, 'IN')
        ->execute();

      // Switch duplicate ids out with the primary id.
      if ($author_nodes && ($author_id !== $primary_author_id)) {
        foreach ($author_nodes as $node_id) {
          $node_storage = $this->entityTypeManager->getStorage('node');
          $node = $node_storage->load($node_id);
          $author_list = $node->get('field_authors')->getValue();
          $key = array_search($author_id, array_column($author_list, 'target_id'));
          $node->get('field_authors')->removeItem($key);
          $node->field_authors[] = ['target_id' => $primary_author_id];
          $node->save();
          echo "Primary Author: " . $primary_author_id . "\n";
          echo "Duplicate: " . $author_id . "\n";
          echo "Node Updated: " . $node_id . "\n";
        }
      }
      // Delete duplicate author.
      $excess_author = WRIAuthor::load($author_id);
      if ($author_id !== $primary_author_id) {
        echo "Duplicate Deleted: " . $author_id . "\n\n";
        $excess_author->delete();
      }
    }
  }

}
