<?php

namespace Drupal\wri_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
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
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entityTypeManager, EntityFieldManager $entityFieldManager) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Check paragraphs for translation inconsistencies.
   */
  public function checkParagraphs() {
    $header = [
      $this->t('Entity ID'),
      $this->t('Entity Type'),
      $this->t('Content Type'),
      $this->t('Paragraph Field'),
      $this->t('Issues'),
      $this->t('Operations'),
    ];

    $rows = [];
    foreach (['paragraph','block_content'] as $entity_type) {

      // Get all content types.
      $type = $entity_type . '_type';
      if ($entity_type == 'paragraph') {
        $type = 'paragraphs_type';
      }
      $entity_bundles = $this->entityTypeManager->getStorage($type)->loadMultiple();

      foreach ($entity_bundles as $bundle_id => $bundle) {
        // Get the fields for this content type.
        $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle_id);

        foreach ($fields as $field_name => $field) {

          // Check for paragraph references.
          if (!in_array($field->getType(), ['entity_reference', 'entity_reference_revisions']) ||
            $field->getSetting('target_type') !== 'paragraph') {
            continue;
          }

          // Load entitys of this content type with translations.
          $entitys = $this->entityTypeManager->getStorage($entity_type)->loadByProperties(['type' => $bundle_id]);

          foreach ($entitys as $entity) {
            if (!$entity->isTranslatable()) {
              continue;
            }

            // Check for translations.
            $translations = $entity->getTranslationLanguages();
            foreach ($translations as $langcode => $language) {
              $translated_entity = $entity->getTranslation($langcode);

              // Get paragraph IDs for original and translation.
              $original_paragraph_ids = [];
              $translated_paragraph_ids = [];
              if ($entity->hasField($field_name)) {
                $original_paragraph_ids = array_column($entity->get($field_name)->getValue(), 'target_id');
              }
              if ($translated_entity->hasField($field_name)) {
                $translated_paragraph_ids = array_column($translated_entity->get($field_name)->getValue(), 'target_id');
              }

              // Compare the IDs.
              $missing_in_translation = array_diff($original_paragraph_ids, $translated_paragraph_ids);
              $orphaned_in_translation = array_diff($translated_paragraph_ids, $original_paragraph_ids);

              // Add rows for discrepancies.
              if (!empty($missing_in_translation) || !empty($orphaned_in_translation)) {
                $rows[] = [
                  'entity_id' => $entity->id(),
                  'entity_type' => $entity_type,
                  'content_type' => $entity->bundle(),
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
                          'url' => Url::fromRoute('wri_admin.add_missing', ['entity_id' => $entity->id()]),
                        ],
                        'remove_orphans' => [
                          'title' => $this->t('Remove Orphans'),
                          'url' => Url::fromRoute('wri_admin.remove_orphans', ['entity_id' => $entity->id()]),
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
  public function addMissingParagraphs($entity_id, $entity_type) {
    // Load the original entity.
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    if (!$entity) {
      $this->messenger()->addError($this->t('entity @id not found.', ['@id' => $entity_id]));
      return $this->redirect('wri_admin.paragraph_checker');
    }

    if (!$entity->isTranslatable()) {
      $this->messenger()->addError($this->t('entity @id is not translatable.', ['@id' => $entity_id]));
      return $this->redirect('wri_admin.paragraph_checker');
    }

    // Get the list of translations for the entity.
    $translations = $entity->getTranslationLanguages();

    foreach ($translations as $langcode => $language) {
      // Skip the original language.
      if ($langcode === $entity->language()->getId()) {
        continue;
      }

      // Load the translated entity.
      $translated_entity = $entity->getTranslation($langcode);

      // Process each paragraph field.
      $paragraph_fields = $this->getParagraphFields($entity);
      foreach ($paragraph_fields as $field_name) {
        // Get the original and translated paragraphs.
        $original_paragraphs = $entity->get($field_name)->referencedEntities();
        $translated_paragraphs = $translated_entity->get($field_name)->referencedEntities();

        // Extract IDs for comparison.
        $original_ids = array_map(fn($paragraph) => $paragraph->id(), $original_paragraphs);
        $translated_ids = array_map(fn($paragraph) => $paragraph->id(), $translated_paragraphs);

        // Identify missing paragraphs.
        $missing_ids = array_diff($original_ids, $translated_ids);
        if (empty($missing_ids)) {
          continue;
        }

        // Reuse the original paragraph IDs in the translation.
        $updated_paragraphs = $translated_entity->get($field_name)->getValue();

        foreach ($original_paragraphs as $original_paragraph) {
          if (in_array($original_paragraph->id(), $missing_ids)) {
            $updated_paragraphs[] = [
              'target_id' => $original_paragraph->id(),
              'target_revision_id' => $original_paragraph->getRevisionId(),
            ];
          }
        }

        // Update the translated entity's field with the updated paragraphs.
        $translated_entity->set($field_name, $updated_paragraphs);
      }

      // Mark as a new revision to force save.
      $translated_entity->setNewRevision(TRUE);
      $translated_entity->save();
    }

    // Display a success message.
    $this->messenger()->addMessage($this->t('Missing paragraphs have been added for entity @id.', ['@id' => $entity_id]));
    return $this->redirect('wri_admin.paragraph_checker');
  }

  /**
   * {@inheritdoc}
   */
  public function removeOrphanedParagraphs($entity_id, $entity_type) {
    // Load the original entity.
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    if (!$entity) {
      $this->messenger()->addError($this->t('entity @id not found.', ['@id' => $entity_id]));
      return $this->redirect('wri_admin.paragraph_checker');
    }

    if (!$entity->isTranslatable()) {
      $this->messenger()->addError($this->t('entity @id is not translatable.', ['@id' => $entity_id]));
      return $this->redirect('wri_admin.paragraph_checker');
    }

    // Get the list of translations for the entity.
    $translations = $entity->getTranslationLanguages();

    foreach ($translations as $langcode => $language) {
      // Skip the original language.
      if ($langcode === $entity->language()->getId()) {
        continue;
      }

      // Load the translated entity.
      $translated_entity = $entity->getTranslation($langcode);

      // Process each paragraph field.
      $paragraph_fields = $this->getParagraphFields($entity);
      foreach ($paragraph_fields as $field_name) {
        // Get the original and translated paragraphs.
        $original_paragraphs = $entity->get($field_name)->referencedEntities();
        $translated_paragraphs = $translated_entity->get($field_name)->referencedEntities();

        // Extract IDs for comparison.
        $original_ids = array_map(fn($paragraph) => $paragraph->id(), $original_paragraphs);
        $translated_ids = array_map(fn($paragraph) => $paragraph->id(), $translated_paragraphs);

        // Identify orphaned paragraphs.
        $orphaned_ids = array_diff($translated_ids, $original_ids);
        if (empty($orphaned_ids)) {
          continue;
        }

        // Remove orphaned paragraphs from the translated entity's field.
        $updated_paragraphs = array_filter(
          $translated_entity->get($field_name)->getValue(),
          fn($item) => !in_array($item['target_id'], $orphaned_ids)
        );

        $translated_entity->set($field_name, $updated_paragraphs);

        // Delete the orphaned paragraph entities to clean up the database.
        foreach ($translated_paragraphs as $translated_paragraph) {
          if (in_array($translated_paragraph->id(), $orphaned_ids)) {
            $translated_paragraph->delete();
          }
        }
      }

      // Mark as a new revision to force save.
      $translated_entity->setNewRevision(TRUE);
      $translated_entity->save();
    }

    // Display a success message.
    $this->messenger()->addMessage($this->t('Orphaned paragraphs have been removed for entity @id.', ['@id' => $entity_id]));
    return $this->redirect('wri_admin.paragraph_checker');
  }

  /**
   * {@inheritdoc}
   */
  private function getParagraphFields($entity, $entity_type) {
    $fields = [];
    foreach ($entity->getFieldDefinitions() as $field_name => $field) {
      if ($field->getType() === 'entity_reference_revisions' && $field->getSetting('target_type') === 'paragraph') {
        $fields[] = $field_name;
      }
    }
    return $fields;
  }

}
