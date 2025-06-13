<?php

namespace Drupal\wri_zoom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Zoom & ORTO integration settings form.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'wri_zoom.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zoom_orto_settings';
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
    // It's not clear to me how else to get the "in use" version of the
    // configuration that accepts settings.php based changes, so we have to:
    // @codingStandardsIgnoreStart
    $overridden_settings = \Drupal::config(self::SETTINGS)->get();
    // @codingStandardsIgnoreEnd
    $form['orto_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable ORTO reporting'),
      '#description' => $this->t("If disabled, no reporting will be sent to ORTO at all. Enable only during testing or on live sites of record."),
      '#default_value' => $settings['orto_enabled'] ?? FALSE,
    ];
    if ($settings['orto_enabled'] != $overridden_settings['orto_enabled']) {
      $form['orto_enabled']['#title'] .= ' <i>[' . $this->t('NOTE: Overridden in settings.php') . ']</i>';
    }
    $form['orto_registration_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Orto Registration URL'),
      '#description' => $this->t("1 hour after the scheduled end time of an event with a zoom webinar ID, send the event to this url. Example: https://ortto.wri.org/zoom/import-participants/?webinarId=[WEBINARID]&webinarName=[WEBINAR_NAME]&webinarDate=[WEBINAR_DATE]. This field accepts tokens."),
      '#default_value' => $settings['orto_registration_url'] ?? '',
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
