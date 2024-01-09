<?php

namespace Drupal\wri_engagement\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform submission debug handler.
 *
 * @WebformHandler(
 *   id = "add_cookie",
 *   label = @Translation("Add Cookie"),
 *   category = @Translation("Add Cookie"),
 *   description = @Translation("Add a cookie on webform submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class AddCookieWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'cookie_name' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Name.
    $form['cookie_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie name'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['cookie_name'],
    ];
    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    if ($this->configuration['cookie_name']) {
      $params = session_get_cookie_params();
      setcookie($this->configuration['cookie_name'], TRUE, time() + 31556952, '/', $params['domain'], $params['secure'], $params['httponly']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['cookie_name'] = $form_state->getValue('cookie_name');
    parent::submitConfigurationForm($form, $form_state);
  }

}
