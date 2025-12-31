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
    print "";
  }

}
