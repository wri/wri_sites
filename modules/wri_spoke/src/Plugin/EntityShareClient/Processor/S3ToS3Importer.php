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
 *   id = "s3_to_s3_importer",
 *   label = @Translation("S3 to S3 Importer"),
 *   description = @Translation("Save the S3 image in a way that it can generate derivatives."),
 *   stages = {
 *     "process_entity" = 0,
 *   },
 *   locked = false,
 * )
 */
class S3ToS3Importer extends ImportProcessorPluginBase implements PluginFormInterface {

  /**
   * The s3fs stream wrapper.
   *
   * @var \Drupal\s3fs\StreamWrapper\S3fsStream
   */
  protected $s3fsStream;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->s3fsStream = $container->get('stream_wrapper.s3fs', ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.ErrorControlOperator)
   */
  public function processEntity(RuntimeImportContext $runtime_import_context, ContentEntityInterface $processed_entity, array $entity_json_data) {
    if ($processed_entity instanceof FileInterface && isset($this->s3fsStream)) {
      $uri = $processed_entity->getFileUri();
      if (strpos($uri, 's3://') === 0) {
        $this->s3fsStream->writeUriToCache($uri);
      }
    }
  }

}
