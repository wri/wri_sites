<?php

namespace Drupal\wri_jobs\Plugin\DsField;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders Past Event if the event is over.
 *
 * @DsField(
 *   id = "show_backlink",
 *   title = @Translation("Shows a link back to the Jobs listing page."),
 *   entity_type = "node",
 *   ui_limit = {"jobs|*"}
 * )
 */
class ShowBackLink extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $info = [];

    $info['#markup'] = '<p><a href="' . $this->configuration['back_link'] . '">' . $this->configuration['link_text'] . '</a></p>';

    return $info;
  }


  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $form['back_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Back Link URL'),
      '#default_value' => isset($this->configuration['back_link']) ? $this->configuration['back_link'] : '/jobs',
      '#required' => TRUE,
    ];
    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => isset($this->configuration['link_text']) ? $this->configuration['link_text'] : 'Back to Job Listings',
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $summary = [];

    $summary[] = $this->t('Back Link URL: @url', ['@url' => isset($this->configuration['back_link']) ? $this->configuration['back_link'] : '/jobs']);
    $summary[] = $this->t('Link Text: @text', ['@text' => isset($this->configuration['link_text']) ? $this->configuration['link_text'] : 'Back to Job Listings']);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'back_link' => '#',
      'link_text' => 'Back to Job Listings',
    ];
  }

}
