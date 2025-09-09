<?php

declare(strict_types=1);

namespace Drupal\wri_spoke\Plugin\EntityShareClient\Processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\entity_share_client\ImportProcessor\ImportProcessorPluginBase;
use Drupal\entity_share_client\RuntimeImportContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Set a default value for registration fields based on canonical URL.
 *
 * @ImportProcessor(
 *   id = "default_registration_link",
 *   label = @Translation("Populate registration link if blank"),
 *   description = @Translation("If there's no link value, insert the hub canonical URL with the #register anchor."),
 *   stages = {
 *     "prepare_importable_entity_data" = 20,
 *   },
 *   locked = false,
 * )
 */
class DefaultRegistrationLink extends ImportProcessorPluginBase implements PluginFormInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityFieldManager = $container->get('entity_field.manager');
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
   */
  public function prepareImportableEntityData(RuntimeImportContext $runtime_import_context, array &$entity_json_data): void {
      $zoom_id = $entity_json_data['attributes']['field_zoom_webinar_id'] ?? false;

    if (empty($entity_json_data['attributes']['field_register']) && $zoom_id) {
      $entity_json_data['attributes']['field_register'] = $entity_json_data['attributes']['field_hub_canonical_url'] . '#register';
    }
  }

}
