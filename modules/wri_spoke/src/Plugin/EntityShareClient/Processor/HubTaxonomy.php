<?php

declare(strict_types=1);

namespace Drupal\wri_spoke\Plugin\EntityShareClient\Processor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_share_client\Attribute\ImportProcessor;
use Drupal\entity_share_client\ImportProcessor\ImportProcessorPluginBase;
use Drupal\entity_share_client\RuntimeImportContext;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Import Hub terms, mapping to other taxonomies.
 */
#[ImportProcessor(
  id: 'hub_taxonomy_importer',
  label: new TranslatableMarkup('Hub Taxonomy Importer'),
  description: new TranslatableMarkup('Syncs local terms with hub terms.'),
  stages: [
    'prepare_importable_entity_data' => 0,
    'process_entity' => 31,
  ],
  locked: FALSE,
)]
class HubTaxonomy extends ImportProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function prepareImportableEntityData(RuntimeImportContext $runtime_import_context, array &$entity_json_data) {
    [$entity_type, $vocabulary] = explode('--', $entity_json_data['type']);
    if ($entity_type !== 'taxonomy_term') {
      return;
    }
    $entity_json_data["type"] = 'taxonomy_term--hub_terms';
    $uuid = $entity_json_data['id'] ?? NULL;
    if ($uuid) {
      // Check if term with this UUID exists locally.
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['uuid' => $uuid]);

      if (empty($terms)) {
        // See if the term matches an existing term by name in the same
        // vocabulary.
        $name = $entity_json_data["attributes"]["name"] ?? NULL;
        if ($name && $vocabulary) {
          $existing_terms = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties([
              'name' => $name,
              'vid' => $vocabulary,
            ]);
          if (!empty($existing_terms)) {
            $this->updateUniqueIdentifierOfExistingTerm($existing_terms, $uuid);
          }
          else {
            // If the term does not exist on the site, change the VID of the
            // term to "Hub terms" and set the original vid to be the value of
            // "Original Vocabulary".
            $entity_json_data["attributes"]["field_original_vocabulary"] = $vocabulary;
            $entity_json_data["relationships"]["parent"]["data"] = [];
            $entity_json_data["type"] = 'taxonomy_term--hub_terms';
          }
        }
      }
      else {
        // Just make sure the vocabulary continues to be what it was.
        $existing_term = reset($terms);
        // If a term with one of the "Hub term"s is pulled, review the values in
        // the "Automatically map" field and add those terms to the node
        // referencing it.
        $entity_json_data["type"] = 'taxonomy_term--' . $existing_term->bundle();
        // And make sure the parent stays empty.
        if ($existing_term->bundle() == 'hub_terms') {
          $entity_json_data["relationships"]["parent"]["data"] = [];
        }
      }
    }
  }

  /**
   * Update the UUID of a term.
   *
   * If a term with the same name exists in the same vocabulary, update
   * the UUID of the existing term to match the import data and update
   * any config entities that reference the old UUID to reference the new.
   */
  private function updateUniqueIdentifierOfExistingTerm($existing_terms, $uuid) {
    // Map the existing term's ID to the import data.
    $existing_term = reset($existing_terms);
    $old_uuid = $existing_term->get('uuid')->value;
    $existing_term->set('uuid', $uuid);
    $existing_term->save();
    // Find/replace any config values referencing the old term ID.
    // Query the database.
    $query = \Drupal::database()->select('config', 'c');
    // Add fields to select.
    $query->fields('c', ['data', 'name', 'collection']);

    // Add a condition (WHERE clause).
    $query->condition('c.data', "%" . \Drupal::database()->escapeLike($old_uuid) . "%", 'LIKE');

    // Execute the query and fetch results.
    $results = $query->execute()->fetchAll();

    foreach ($results as $record) {
      $config_data = unserialize($record->data, ['allowed_classes' => FALSE]);
      $updated = FALSE;

      // Recursively search and replace the old UUID with the new UUID.
      $replace_uuid = function (&$data) use (&$replace_uuid, $old_uuid, $uuid, &$updated) {
        if (is_array($data)) {
          foreach ($data as &$value) {
            $replace_uuid($value);
          }
        }
        elseif (is_string($data) && str_contains($data, $old_uuid)) {
          $data = str_replace($old_uuid, $uuid, $data);
          $updated = TRUE;
        }
      };

      $replace_uuid($config_data);

      // If any replacements were made, save the updated config.
      if ($updated) {
        $config = \Drupal::configFactory()->getEditable($record->name);
        $config->setData($config_data);
        $config->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * This processor should be run AFTER the Entity reference one.
   */
  public function processEntity(RuntimeImportContext $runtime_import_context, ContentEntityInterface $processed_entity, array $entity_json_data) {
    if ($processed_entity instanceof NodeInterface) {
      // Load all the term reference fields on the node.
      // Loop through all of them.
      foreach ($processed_entity->getFields() as $field) {
        if ($field->getFieldDefinition()->getType() !== 'entity_reference') {
          continue;
        }
        $field_name = $field->getName();
        $field_values = $processed_entity->get($field_name)->referencedEntities();
        $new_values = [];
        foreach ($field_values as $related_entity) {
          // By default, just save the term.
          $new_value = $related_entity;
          // If the related entity is a term, check if it belongs to the
          // "Hub terms" vocabulary.
          if ($related_entity instanceof Term) {
            $actual_vocabulary = $related_entity->bundle();
            if ($actual_vocabulary == 'hub_terms') {
              // If it does, see if that term has any AKA values.
              $aka_term = $related_entity->field_also_known_as->entity;

              // If it does, set the value of the field to match the AKA value.
              if ($aka_term) {
                $new_value = $aka_term;
              }
            }
          }
          $new_values[] = $new_value;
        }
        $processed_entity->set($field_name, $new_values);
      }
    }
  }

}
