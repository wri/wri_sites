<?php

declare(strict_types=1);

namespace Drupal\wri_spoke\Plugin\EntityShareClient\Processor;

use Drupal\Component\DependencyInjection\Container;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\file\FileInterface;
use Drupal\entity_share_client\ImportProcessor\ImportProcessorPluginBase;
use Drupal\entity_share_client\RuntimeImportContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Import S3 files to S3 in a way that they can generate derivatives.
 *
 * @ImportProcessor(
 *   id = "hub_taxonomy_importer",
 *   label = @Translation("Hub Taxonomy Importer"),
 *   description = @Translation("Syncs local terms with hub terms."),
 *   stages = {
 *     "prepare_importable_entity_data" = 0,
 *   },
 *   locked = false,
 * )
 */
class HubTaxonomy extends ImportProcessorPluginBase {
  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.ErrorControlOperator)
   */
  public function prepareImportableEntityData(RuntimeImportContext $runtime_import_context, array &$entity_json_data) {
    [$entity_type, $vocabulary] = explode('--', $entity_json_data['type']);
    if ($entity_type !== 'taxonomy_term') {
      return;
    }
    $entity_json_data["type"] = 'taxonomy_term--hub_terms';
    $uuid = $entity_json_data['id'] ?? null;
    if ($uuid) {
      // Check if term with this UUID exists locally.
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['uuid' => $uuid]);

      if (empty($terms)) {
        // See if the term matches an existing term by name in the same vocabulary.
        $name = $entity_json_data["attributes"]["name"] ?? null;
        if ($name && $vocabulary) {
          $existing_terms = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties([
              'name' => $name,
              'vid' => $vocabulary,
            ]);
          if (!empty($existing_terms)) {
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
            $query->condition('c.data', $old_uuid, 'LIKE');

            // Execute the query and fetch results.
            $results = $query->execute()->fetchAll();

            foreach ($results as $record) {
              $config_data = unserialize($record->data);
              $updated = FALSE;

              // Recursively search and replace the old UUID with the new UUID.
              $replace_uuid = function(&$data) use (&$replace_uuid, $old_uuid, $uuid, &$updated) {
                if (is_array($data)) {
                  foreach ($data as &$value) {
                    $replace_uuid($value);
                  }
                }
                elseif (is_string($data) && $data === $old_uuid) {
                  $data = $uuid;
                  $updated = TRUE;
                }
              };

              $replace_uuid($config_data);

              // If any replacements were made, save the updated config.
              if ($updated) {
                $config = \Drupal::configFactory()->getEditable($record->name);
                // Find the uuid.
                print "";
              }
            }
          }
        }
      } else {
        // Just make sure the vocabulary continues to be what it was.
        $existing_term = reset($terms);
        $entity_json_data["type"] = 'taxonomy_term--' . $existing_term->bundle();
      }
    }
  }

}
