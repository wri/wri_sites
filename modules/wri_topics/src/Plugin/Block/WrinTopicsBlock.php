<?php

namespace Drupal\wri_topics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a wrin topics block.
 *
 * @Block(
 *   id = "single_file_component_block:wri_topics_block",
 *   admin_label = @Translation("WRIN Topics"),
 *   category = @Translation("Custom")
 * )
 */
class WrinTopicsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'topic_intro_text' => $this->t('Intro Text'),
      'hide_centers' => $this->t('Hide Centers'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $default_values = $this->configuration['component_context'] ?? $this->configuration;
    $form['topic_intro_text'] = [
      '#type'   => 'textarea',
      '#title'  => $this->t('Intro Text'),
      '#default_value' => $default_values['topic_intro_text'] ?? '',
    ];
    $form['hide_centers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Centers'),
      '#default_value' => $default_values['hide_centers'] ?? '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['topic_intro_text'] = $form_state->getValue('topic_intro_text');
    $this->configuration['hide_centers'] = $form_state->getValue('hide_centers');
    unset($this->configuration['component_context']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = $this->configuration['component_context'] ?? $this->configuration;

    $build['content'] = [
      '#theme' => 'wri_topics_block',
      '#topic_intro_text' => $values['topic_intro_text'],
      '#hide_centers' => $values['hide_centers'] ?? FALSE,
    ];
    return $build;
  }

}
