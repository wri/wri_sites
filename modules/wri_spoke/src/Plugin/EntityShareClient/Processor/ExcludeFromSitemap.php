<?php

declare(strict_types=1);

namespace Drupal\wri_spoke\Plugin\EntityShareClient\Processor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_share_client\Attribute\ImportProcessor;
use Drupal\entity_share_client\ImportProcessor\ImportProcessorPluginBase;
use Drupal\entity_share_client\RuntimeImportContext;

/**
 * Exclude imported content from the XML sitemap.
 */
#[ImportProcessor(
  id: 'exclude_from_sitemap',
  label: new TranslatableMarkup('Exclude from sitemap'),
  description: new TranslatableMarkup('Sets xmlsitemap status to Exclude on all imported content so hub content does not appear in spoke sitemaps.'),
  stages: [
    'process_entity' => 50,
  ],
  locked: FALSE,
)]
class ExcludeFromSitemap extends ImportProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function processEntity(RuntimeImportContext $runtime_import_context, ContentEntityInterface $processed_entity, array $entity_json_data): void {
    [$entity_type] = explode('--', $entity_json_data['type']);
    if ($entity_type !== 'node') {
      return;
    }
    if (!is_array($processed_entity->xmlsitemap ?? NULL)) {
      $processed_entity->xmlsitemap = [];
    }
    $processed_entity->xmlsitemap['status'] = 0;
    $processed_entity->xmlsitemap['status_override'] = 1;
  }

}
