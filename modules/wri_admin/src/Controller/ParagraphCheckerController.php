<?php

namespace Drupal\wri_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for checking paragraph translations.
 */
class ParagraphCheckerController extends ControllerBase {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Check paragraphs for translation inconsistencies.
   */
  public function checkParagraphs() {
    $header = [
      $this->t('Node ID'),
      $this->t('Content Type'),
      $this->t('Paragraph Field'),
      $this->t('Issues'),
      $this->t('Operations'),
    ];

    $rows = [];

    // Get all content types.
    $node_bundles = \Drupal::service('entity_type.manager')
      ->getStorage('node_type')
      ->loadMultiple();

    foreach ($node_bundles as $bundle_id => $bundle) {
      // Get the fields for this content type.
      $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $bundle_id);

      foreach ($fields as $field_name => $field) {

        // Check for paragraph references.
        if (!in_array($field->getType(), ['entity_reference', 'entity_reference_revisions']) ||
            $field->getSetting('target_type') !== 'paragraph') {
          continue;
        }

        // Load nodes of this content type with translations.
        $nodes = \Drupal::entityTypeManager()->getStorage('node')
          ->loadByProperties(['type' => $bundle_id]);

        foreach ($nodes as $node) {
          if (!$node->isTranslatable()) {
            continue;
          }

          // Check for translations.
          $translations = $node->getTranslationLanguages();
          foreach ($translations as $langcode => $language) {
            $translated_node = $node->getTranslation($langcode);

            // Get paragraph IDs for original and translation.
            $original_paragraph_ids = [];
            $translated_paragraph_ids = [];
            if ($node->hasField($field_name)) {
              $original_paragraph_ids = array_column($node->get($field_name)->getValue(), 'target_id');
            }
            if ($translated_node->hasField($field_name)) {
              $translated_paragraph_ids = array_column($translated_node->get($field_name)->getValue(), 'target_id');
            }

            // Compare the IDs.
            $missing_in_translation = array_diff($original_paragraph_ids, $translated_paragraph_ids);
            $orphaned_in_translation = array_diff($translated_paragraph_ids, $original_paragraph_ids);

            // Add rows for discrepancies.
            if (!empty($missing_in_translation) || !empty($orphaned_in_translation)) {
              $rows[] = [
                'node_id' => $node->id(),
                'content_type' => $node->bundle(),
                'paragraph_field' => $field_name,
                'issues' => Markup::create(
                  '<strong>Missing in Translation:</strong> ' . (!empty($missing_in_translation) ? implode(', ', $missing_in_translation) : 'None') . '<br>' .
                  '<strong>Orphaned in Translation:</strong> ' . (!empty($orphaned_in_translation) ? implode(', ', $orphaned_in_translation) : 'None')
                ),
                'operations' => [
                  'data' => [
                    '#type' => 'operations',
                    '#links' => [
                      'add_missing' => [
                        'title' => $this->t('Add Missing'),
                        'url' => Url::fromRoute('wri_admin.add_missing', ['node_id' => $node->id()]),
                      ],
                      'remove_orphans' => [
                        'title' => $this->t('Remove Orphans'),
                        'url' => Url::fromRoute('wri_admin.remove_orphans', ['node_id' => $node->id()]),
                      ],
                    ],
                  ],
                ],
              ];

            }
          }
        }
      }
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No discrepancies found.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function addMissingParagraphs($node_id) {
    // Load the original node.
    $node = Node::load($node_id);
    if (!$node) {
      $this->messenger()->addError($this->t('Node @id not found.', ['@id' => $node_id]));
      return $this->redirect('wri_admin.paragraph_checker');
    }

    if (!$node->isTranslatable()) {
      $this->messenger()->addError($this->t('Node @id is not translatable.', ['@id' => $node_id]));
      return $this->redirect('wri_admin.paragraph_checker');
    }

    // Get the list of translations for the node.
    $translations = $node->getTranslationLanguages();

    foreach ($translations as $langcode => $language) {
      // Skip the original language.
      if ($langcode === $node->language()->getId()) {
        continue;
      }

      // Load the translated node.
      $translated_node = $node->getTranslation($langcode);

      // Process each paragraph field.
      $paragraph_fields = $this->getParagraphFields($node);
      foreach ($paragraph_fields as $field_name) {
        // Get the original and translated paragraphs.
        $original_paragraphs = $node->get($field_name)->referencedEntities();
        $translated_paragraphs = $translated_node->get($field_name)->referencedEntities();

        // Extract IDs for comparison.
        $original_ids = array_map(fn($paragraph) => $paragraph->id(), $original_paragraphs);
        $translated_ids = array_map(fn($paragraph) => $paragraph->id(), $translated_paragraphs);

        // Identify missing paragraphs.
        $missing_ids = array_diff($original_ids, $translated_ids);
        if (empty($missing_ids)) {
          continue;
        }

        // Reuse the original paragraph IDs in the translation.
        $updated_paragraphs = $translated_node->get($field_name)->getValue();

        foreach ($original_paragraphs as $original_paragraph) {
          if (in_array($original_paragraph->id(), $missing_ids)) {
            $updated_paragraphs[] = [
              'target_id' => $original_paragraph->id(),
              'target_revision_id' => $original_paragraph->getRevisionId(),
            ];
          }
        }

        // Update the translated node's field with the updated paragraphs.
        $translated_node->set($field_name, $updated_paragraphs);
      }

      // Mark as a new revision to force save.
      $translated_node->setNewRevision(TRUE);
      $translated_node->save();
    }

    // Display a success message.
    $this->messenger()->addMessage($this->t('Missing paragraphs have been added for Node @id.', ['@id' => $node_id]));
    return $this->redirect('wri_admin.paragraph_checker');
  }

  /**
   * {@inheritdoc}
   */
  public function removeOrphanedParagraphs($node_id) {
    // Load the original node.
    $node = Node::load($node_id);
    if (!$node) {
      $this->messenger()->addError($this->t('Node @id not found.', ['@id' => $node_id]));
      return $this->redirect('wri_admin.paragraph_checker');
    }

    if (!$node->isTranslatable()) {
      $this->messenger()->addError($this->t('Node @id is not translatable.', ['@id' => $node_id]));
      return $this->redirect('wri_admin.paragraph_checker');
    }

    // Get the list of translations for the node.
    $translations = $node->getTranslationLanguages();

    foreach ($translations as $langcode => $language) {
      // Skip the original language.
      if ($langcode === $node->language()->getId()) {
        continue;
      }

      // Load the translated node.
      $translated_node = $node->getTranslation($langcode);

      // Process each paragraph field.
      $paragraph_fields = $this->getParagraphFields($node);
      foreach ($paragraph_fields as $field_name) {
        // Get the original and translated paragraphs.
        $original_paragraphs = $node->get($field_name)->referencedEntities();
        $translated_paragraphs = $translated_node->get($field_name)->referencedEntities();

        // Extract IDs for comparison.
        $original_ids = array_map(fn($paragraph) => $paragraph->id(), $original_paragraphs);
        $translated_ids = array_map(fn($paragraph) => $paragraph->id(), $translated_paragraphs);

        // Identify orphaned paragraphs.
        $orphaned_ids = array_diff($translated_ids, $original_ids);
        if (empty($orphaned_ids)) {
          continue;
        }

        // Remove orphaned paragraphs from the translated node's field.
        $updated_paragraphs = array_filter(
          $translated_node->get($field_name)->getValue(),
          fn($item) => !in_array($item['target_id'], $orphaned_ids)
        );

        $translated_node->set($field_name, $updated_paragraphs);

        // Delete the orphaned paragraph entities to clean up the database.
        foreach ($translated_paragraphs as $translated_paragraph) {
          if (in_array($translated_paragraph->id(), $orphaned_ids)) {
            $translated_paragraph->delete();
          }
        }
      }

      // Mark as a new revision to force save.
      $translated_node->setNewRevision(TRUE);
      $translated_node->save();
    }

    // Display a success message.
    $this->messenger()->addMessage($this->t('Orphaned paragraphs have been removed for Node @id.', ['@id' => $node_id]));
    return $this->redirect('wri_admin.paragraph_checker');
  }

  /**
   * {@inheritdoc}
   */
  private function getParagraphFields(Node $node) {
    $fields = [];
    foreach ($node->getFieldDefinitions() as $field_name => $field) {
      if ($field->getType() === 'entity_reference_revisions' && $field->getSetting('target_type') === 'paragraph') {
        $fields[] = $field_name;
      }
    }
    return $fields;
  }

}
