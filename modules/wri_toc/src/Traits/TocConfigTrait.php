<?php

declare(strict_types=1);

namespace Drupal\wri_toc\Traits;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides shared config form methods for TOC-style blocks and formatters.
 */
trait TocConfigTrait {

  /**
   * Returns default configuration values.
   */
  protected static function getDefaultTocConfig(): array {
    return [
      'menu' => 'page-hierarchies',
      'color_class' => 'black-bar',
    ];
  }

  /**
   * Builds the shared TOC form elements.
   */
  protected function buildTocForm(array $form, FormStateInterface $form_state, array $config): array {
    $form['menu'] = [
      '#type' => 'textfield',
      '#title' => t('Menu name'),
      '#description' => t('The dash-case name of the menu: i.e., page-hierarchies'),
      '#default_value' => $config['menu'] ?? 'page-hierarchies',
    ];
    $form['color_class'] = [
      '#type' => 'textfield',
      '#title' => t('Color class'),
      '#description' => t('Background color class to apply. Example: black-bar, gold-bar'),
      '#default_value' => $config['color_class'] ?? 'black-bar',
    ];
    return $form;
  }

}
