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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static($container->get('config.factory'));
    $instance->entityTypeManager = $container->get('entity_type.manager');
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
    $options = [];
    foreach ($remotes as $remote) {
      $remote_options[$remote->id()] = $remote->label();
    }
    $form['remote'] = [
      '#type' => 'select',
      '#options' => [
        'hub' => 'WRI Hub',
        'local_hub' => 'Local hub (dev only)',
      ],
      '#title' => $this->t('Remote'),
      '#default_value' => $settings['remote'] ?? '',
    ];
    $form['channel'] = [
      '#type' => 'select',
      '#options' => [
        'events_africa' => 'Events for WRI Africa',
        'events_brasil' => 'Events for WRI Brasil',
        'events_china' => 'Events for WRI China',
        'events_espanol' => 'Events for WRI Espanol',
        'events_flagship' => 'Events for WRI Flagship',
        'events_india' => 'Events for WRI India',
        'events_indonesia' => 'Events for WRI Indonesia',
      ],
      '#title' => $this->t('Channel'),
      '#default_value' => $settings['channel'] ?? '',
    ];
    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Records per Cron run'),
      '#default_value' => $settings['limit'] ?? 20,
    ];
    $form['changed_since'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last synced change time.'),
      '#description' => $this->t('Do not edit this unless you know what you are doing. Setting it to 0 will cause event synchronization to go back to the oldest events on the Hub and work forward slowly.'),
      '#default_value' => $settings['changed_since'] ?? 0,
    ];

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
    $config->save();
  }

}
