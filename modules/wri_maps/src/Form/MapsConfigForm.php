<?php

namespace Drupal\wri_maps\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * WRI Maps configuration form.
 */
class MapsConfigForm extends ConfigFormBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\aggregator\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->setConfigFactory($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wri_maps_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wri_maps.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wri_maps.settings');
    $region_map_file = $config->get('region_map_file');

    $form['region_map_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload region map'),
      '#upload_location' => 'public://',
      '#multiple' => FALSE,
      '#description' => $this->t('Allowed extensions: svg'),
      '#upload_validators' => ['file_validate_extensions' => ['svg']],
      '#accept' => '.svg',
    ];

    if ($region_map_file) {
      $form['region_map_file']['#default_value'][] = $region_map_file;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $region_map_file_array = $form_state->getValue('region_map_file');
    if (isset($region_map_file_array[0])) {
      $file_type = $this->entityTypeManager->getStorage('file');
      $file = $file_type->load($region_map_file_array[0]);
      $file->setPermanent();
      $file->save();
    }

    $config = $this->config('wri_maps.settings');
    $config->set('region_map_file', reset($region_map_file_array));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
