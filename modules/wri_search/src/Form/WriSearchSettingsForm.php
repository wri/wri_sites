<?php

declare(strict_types=1);

namespace Drupal\wri_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Entity\View;

/**
 * Configure WRIN Search settings for this site.
 */
final class WriSearchSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wri_search_wri_search_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['wri_search.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['enable_google'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Google Custom Search'),
      '#description' => $this->t('Enable Google Custom Search for site search functionality.'),
      '#default_value' => $this->config('wri_search.settings')->get('enable_google'),
      '#config_target' => 'wri_search.settings:enable_google',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $view = View::load('search');
    $display =& $view->getDisplay('results');
    if ($form_state->getValue('enable_google')) {
      // If the value of enable_google is true, set the path for search.view to
      // /search/site.
      $display['display_options']['path'] = 'search/site';
    }
    else {
      // Otherwise, set the path for search.view to /search.
      $display['display_options']['path'] = 'search';
    }
    $view->save();

    parent::submitForm($form, $form_state);
  }

}
