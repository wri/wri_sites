<?php

namespace Drupal\wri_engagement\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;

/**
 * Configure WRI Engagement settings for this site.
 */
class WriEngagementForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wri_engagement_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wri_engagement.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $webform_ids = \Drupal::entityQuery('webform')->execute();
    $webforms = Webform::loadMultiple($webform_ids);
    foreach ($webforms as $id => $value) {
      $options[$id] = $value->label();
    }
    $form['download_interrupter'] = [
      '#type' => 'select',
      '#title' => $this->t('File interrupter CTA'),
      '#description' => $this->t('Display this form to users who have not yet provided their information to download files'),
      '#options' => $options,
      '#default_value' => $this->config('wri_engagement.settings')->get('download_interrupter'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('wri_engagement.settings')
      ->set('download_interrupter', $form_state->getValue('download_interrupter'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
