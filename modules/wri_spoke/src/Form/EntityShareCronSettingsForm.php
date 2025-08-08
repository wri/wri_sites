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
    $channel_settings = is_array($settings['channel']) ? $settings['channel'] : [$settings['channel']];
    $form['channel'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'events_africa' => $this->t('Events for WRI Africa'),
        'events_africa_fr' => $this->t('Events for WRI Africa (French)'),
        'events_brasil' => $this->t('Events for WRI Brasil'),
        'events_brasil_pt_br' => $this->t('Events for WRI Brasil (Portuguese, Brazil)'),
        'events_china' => $this->t('Events for WRI China'),
        'events_china_zh_hans' => $this->t('Events for WRI China (Chinese, Simplified'),
        'events_espanol' => $this->t('Events for WRI Espanol'),
        'events_espanol_es' => $this->t('Events for WRI Espanol (Spanish)'),
        'events_flagship' => $this->t('Events for WRI Flagship'),
        'events_india' => $this->t('Events for WRI India'),
        'events_indonesia' => $this->t('Events for WRI Indonesia'),
        'events_indonesia_id' => $this->t('Events for WRI Indonesia (Indonesian)'),
      ],
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
        '#description' => $this->t('Do not edit this unless you know what you are doing. Setting it to 0 will cause event synchronization to go back to the oldest events on the Hub and work forward slowly.'),
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
