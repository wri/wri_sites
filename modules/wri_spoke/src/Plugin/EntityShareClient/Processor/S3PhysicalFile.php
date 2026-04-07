<?php

declare(strict_types=1);

namespace Drupal\wri_spoke\Plugin\EntityShareClient\Processor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\entity_share_client\Plugin\EntityShareClient\Processor\PhysicalFile;
use Drupal\entity_share_client\RuntimeImportContext;
use Drupal\file\FileInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handle physical file import.
 *
 * @ImportProcessor(
 *   id = "s3_physical_file",
 *   label = @Translation("S3 Physical file"),
 *   description = @Translation("When importing a File entity from S3, also import the physical file."),
 *   stages = {
 *     "prepare_importable_entity_data" = 0,
 *     "process_entity" = 0,
 *   },
 *   locked = false,
 * )
 */
class S3PhysicalFile extends PhysicalFile {

  /**
   * The Guzzle HTTP Client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->httpClient = $container->get('http_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareImportableEntityData(RuntimeImportContext $runtime_import_context, array &$entity_json_data) {
    if ($entity_json_data['type'] == 'file--file') {
      $entity_json_data["attributes"]["uri"]["value"] = str_replace('s3://', 'public://', $entity_json_data["attributes"]["uri"]["value"]);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.ErrorControlOperator)
   */
  public function processEntity(RuntimeImportContext $runtime_import_context, ContentEntityInterface $processed_entity, array $entity_json_data) {
    if ($processed_entity instanceof FileInterface) {
      $field_mappings = $runtime_import_context->getFieldMappings();
      $entity_type_id = $processed_entity->getEntityTypeId();
      $entity_bundle = $processed_entity->bundle();

      $uri_public_name = FALSE;
      if (isset($field_mappings[$entity_type_id][$entity_bundle]['uri'])) {
        $uri_public_name = $field_mappings[$entity_type_id][$entity_bundle]['uri'];
      }

      if (!$uri_public_name || !isset($entity_json_data['attributes'][$uri_public_name])) {
        $this->logger->error('Impossible to get the URI of the file in JSON:API data. Please check that the server website is correctly exposing it.');
        $this->messenger()->addError($this->t('Impossible to get the URI of the file in JSON:API data. Please check that the server website is correctly exposing it.'));
        return;
      }

      $remote_file_uri = $entity_json_data['attributes'][$uri_public_name]['value'];
      // The following is needed to change s3 images to public.
      $remote_file_url = $entity_json_data['attributes'][$uri_public_name]['url'];
      $stream_wrapper = $this->streamWrapperManager->getViaUri($remote_file_uri);
      $directory_uri = $stream_wrapper->dirname($remote_file_uri);
      $log_variables = [
        '%url' => $remote_file_url,
        '%directory' => $directory_uri,
        '%id' => $processed_entity->id(),
        '%uri' => $remote_file_uri,
      ];

      $file_overwrite_mode = $this->configuration['rename']
        ? FileSystemInterface::EXISTS_RENAME
        : FileSystemInterface::EXISTS_REPLACE;

      // Slight difference from parent: First argument is $remote_file_uri.
      $file_destination = $this->fileSystem->getDestinationFilename($remote_file_uri, $file_overwrite_mode);
      $processed_entity->setFileUri($file_destination);

      // Create the destination folder.
      if ($this->fileSystem->prepareDirectory($directory_uri, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        try {
          // Try opening the file first, to avoid calling prepareDirectory()
          // unnecessarily. We're suppressing fopen() errors because we want to
          // try to prepare the directory before we give up and fail.
          $destination_stream = @fopen($file_destination, 'w');
          try {
            $this->httpClient->get($remote_file_url, ['sink' => $destination_stream]);
          }
          catch (\Exception $e) {
            throw new \Exception("{$e->getMessage()} ($remote_file_url)");
          }

          if (is_resource($destination_stream)) {
            fclose($destination_stream);
          }
        }
        catch (ClientException $e) {
          $this->logger->warning('Error importing file id %id. Missing file: %url', $log_variables);
          $this->messenger()->addWarning($this->t('Error importing file id %id. Missing file: %url', $log_variables));
        }
        catch (\Throwable $e) {
          $log_variables['@msg'] = $e->getMessage();
          $this->logger->error('Caught exception trying to import the file %url to %uri. Error message was @msg', $log_variables);
          $this->messenger()->addError($this->t('Caught exception trying to import the file %url to %uri', $log_variables));
        }
      }
      else {
        $this->logger->error('Impossible to write in the directory %directory', $log_variables);
        $this->messenger()->addError($this->t('Impossible to write in the directory %directory', $log_variables));
      }
    }
  }

}
