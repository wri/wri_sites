<?php

declare(strict_types=1);

namespace Drupal\wri_spoke\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure WRI Spoke settings for this site.
 */
final class WriSpokeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wri_spoke_wri_spoke_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['wri_spoke.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['ignore_hub_url'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not link nodes to the Hub'),
      '#config_target' => 'wri_spoke.settings:ignore_hub_url',
      '#description' => $this->t('By default, the Spoke client will rewrite URLs to point to the Hub. Enable this setting to disable that behavior. You should only use this if you are on the hub itself.'),
    ];
    return parent::buildForm($form, $form_state);
  }

}
