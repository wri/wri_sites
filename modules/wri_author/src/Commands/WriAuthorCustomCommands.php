<?php

namespace Drupal\wri_author\Commands;

use Drupal\Core\Database\Connection;
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
   * An instance of the database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
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
      ->groupBy('field_person')
      ->groupBy('field_person_link')
      ->condition('type', $type)
      ->conditionAggregate('name', 'COUNT', '1', '>')
      ->range(0, $number)
      ->execute();

    // Get duplicate author IDs by type.
    foreach ($author_list as $author) {
      if (isset($author['name'])) {
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
   * Drush command to merge empty external authors.
   *
   * @param int $number
   *   Number of Authors to process.
   *
   * @command wri_author:merge-empty-external-authors
   * @usage wri_author:merge-empty-external-authors 50
   */
  public function mergeEmptyExternalAuthors($number = 5) {
    // Find any authors with no link value.
    // SELECT d1.id  FROM wri_author_field_data d1
    // LEFT JOIN wri_author__field_person_link on (id=entity_id)
    // LEFT JOIN wri_author_field_data d2 USING(name)
    // WHERE d1.type='external'
    // AND d2.type='external'
    // AND field_person_link_uri IS NULL
    // AND d1.id <> d2.id;.
    $query = $this->database->select('wri_author_field_data', 'd1')
      ->fields('d1', ['id', 'name']);
    $query->leftJoin('wri_author__field_person_link', 'link', 'd1.id=link.entity_id');
    $query->leftJoin('wri_author_field_data', 'd2', 'd1.name=d2.name');
    $query->addField('d2', 'id', 'primary_id');
    $query->condition('d1.type', 'external')
      ->condition('d2.type', 'external')
      ->where('d2.id <> d1.id');
    $author_list = $query->isNull('link.field_person_link_uri')
      ->execute();

    // Get duplicate author IDs by type.
    foreach ($author_list as $author) {
      if ($duplicate_authors = $author->id) {
        $primary_author_id = $author->primary_id;

        // Helper function to replace & remove duplicate authors.
        $this->replaceDuplicateAuthors([$duplicate_authors], $primary_author_id);
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
    // SELECT * FROM node__field_authors LEFT JOIN  wri_author ON
    // (field_authors_target_id=id) WHERE uuid IS NULL.
    $query = $this->database->select('node__field_authors', 'node_auths')
      ->fields('node_auths', ['field_authors_target_id']);
    $query->leftJoin('wri_author', 'auths', 'node_auths.field_authors_target_id=auths.id');
    $empty_authors = $query->isNull('auths.uuid')
      ->execute()->fetchCol();

    // Get author IDs without a name.
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
          $node->field_authors[$key] = ['target_id' => $primary_author_id];
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
