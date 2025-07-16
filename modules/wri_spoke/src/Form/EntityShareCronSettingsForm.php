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
