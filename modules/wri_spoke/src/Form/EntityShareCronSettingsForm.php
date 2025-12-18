<?php

namespace Drupal\wri_spoke\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity Share Cron settings form.
 */
class EntityShareCronSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'wri_spoke.cron.settings';

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The remote manager.
   *
   * @var \Drupal\entity_share_client\Service\RemoteManagerInterface
   */
  protected $remoteManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static($container->get('config.factory'));
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->remoteManager = $container->get('entity_share_client.remote_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_share_cron_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $settings = $this->config(self::SETTINGS)->get();
    $remotes = $this->entityTypeManager->getStorage('remote')->loadMultiple();
    $remote_options = [];
    foreach ($remotes as $remote) {
      $remote_options[$remote->id()] = $remote->label();
    }
    $form['remote'] = [
      '#type' => 'select',
      '#options' => $remote_options,
      '#title' => $this->t('Remote'),
      '#default_value' => $settings['remote'] ?? '',
    ];

    $import_configs = $this->entityTypeManager->getStorage('import_config')->loadMultiple();
    foreach ($import_configs as $import_config) {
      $import_config_options[$import_config->id()] = $import_config->label();
    }

    $form['import_config'] = [
      '#type' => 'select',
      '#options' => $import_config_options,
      '#title' => $this->t('Import_config'),
      '#default_value' => $settings['import_config'] ?? '',
    ];

    $channel_settings = is_array($settings['channel']) ? $settings['channel'] : [$settings['channel']];
    try {
      $channels_available = $this->remoteManager->getChannelsInfos($remote, [
        'rethrow' => TRUE,
      ]);
    }
    catch (\Throwable $exception) {
      $channels_available = [];
    }

    $channel_options = [];
    foreach ($channels_available as $channel_id => $channel_info) {
      $channel_options[$channel_id] = $channel_info['label'];
    }
    $form['channel'] = [
      '#type' => 'checkboxes',
      '#options' => $channel_options,
      '#title' => $this->t('Channel'),
      '#default_value' => $channel_settings,
    ];
    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Records per Cron run'),
      '#default_value' => $settings['limit'] ?? 20,
    ];
    $form['changed_since'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Last synced change times by channel'),
      '#tree' => TRUE,
    ];
    foreach ($settings['changed_since'] as $id => $value) {
      $form['changed_since'][$id] = [
        '#type' => 'textfield',
        '#title' => $form['channel']['#options'][$id],
        '#description' => $this->t('Do not edit this unless you know what you are doing. Setting it to 0 will cause synchronization to go back to the oldest nodes and work forward slowly.'),
        '#default_value' => $value ?? 0,
      ];

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->cleanValues();
    $values = $form_state->getValues();
    $config = $this->config(self::SETTINGS);
    foreach ($values as $key => $val) {
      $config->set($key, $val);
    }
    $changed_since = $config->get('changed_since');
    if (!is_array($changed_since)) {
      $changed_since = [
        current($form["channel"]["#value"]) => $changed_since,
      ];
    }
    $new_channels = FALSE;
    foreach ($values["channel"] as $id => $selected) {
      if ($selected && !isset($changed_since[$id])) {
        $changed_since[$id] = 0;
        $new_channels = TRUE;
      }
    }
    if ($new_channels) {
      unset($changed_since[0]);
      unset($changed_since[1]);
      unset($changed_since[2]);
      $config->set('changed_since', $changed_since);
    }
    $config->save();
  }

}
