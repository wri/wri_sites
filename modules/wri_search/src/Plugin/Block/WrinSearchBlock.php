<?php

namespace Drupal\wri_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a wrin search block.
 *
 * @Block(
 *   id = "single_file_component_block:wri_search_menu",
 *   admin_label = @Translation("WRIN Search"),
 *   category = @Translation("Custom")
 * )
 */
class WrinSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'search_nav_title' => '',
      'search_nav_description' => '',
      'search_nav_label' => '',
      'search_nav_id' => '',
      'search_nav_submit' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $default_values = $this->configuration['component_context'] ?? $this->configuration;
    $form['search_nav_title'] = [
      '#type'   => 'textfield',
      '#title'  => $this->t('Search box title'),
      '#default_value' => $default_values['search_nav_title'] ?? 'Search WRI.org',
    ];
    $form['search_nav_description'] = [
      '#type'   => 'textfield',
      '#title'  => $this->t('Search box description'),
      '#default_value' => $default_values['search_nav_description'] ?? 'Not sure where to find something? Search all of the site\'s content.',
    ];
    $form['search_nav_label'] = [
      '#type'   => 'textfield',
      '#title'  => $this->t('Input label'),
      '#default_value' => $default_values['search_nav_label'] ?? 'What can we help you find?',
    ];

    if ($form_state->getFormObject() instanceof EntityFormInterface) {
      $nid = $form_state->getformObject()->getEntity()->id();
    }
    $form['search_nav_id'] = [
      '#type'   => 'hidden',
      '#default_value' => $nid,
    ];
    $form['search_nav_submit'] = [
      '#type'   => 'textfield',
      '#title'  => $this->t('Submit button text'),
      '#default_value' => $default_values['search_nav_submit'] ?? 'Search',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach ($this->defaultConfiguration()['component_context'] as $key => $default) {
      $value = $form_state->getValue($key);
      $this->configuration['component_context'][$key] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $default_values = $this->configuration['component_context'] ?? $this->configuration;
    $build['content'] = [
      '#theme' => 'wri_search_menu',
      '#search_nav_title' => $default_values['search_nav_title'],
      '#search_nav_description' => $default_values['search_nav_description'],
      '#search_nav_label' => $default_values['search_nav_label'],
      '#search_nav_id' => $default_values['search_nav_id'],
      '#search_nav_submit' => $default_values['search_nav_submit'],
    ];
    return $build;
  }

}
