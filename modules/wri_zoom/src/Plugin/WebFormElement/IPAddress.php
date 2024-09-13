<?php

namespace Drupal\wri_zoom\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an 'ip_address' webform element.
 *
 * @WebformElement(
 *   id = "ip_address",
 *   label = @Translation("IP Address"),
 *   description = @Translation("Provides a webform element for capturing IP Address."),
 *   category = @Translation("Custom"),
 * )
 */
class IPAddress extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return parent::defineDefaultProperties() + [
      'ip_address' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    // Fetch the current request.
    $request = \Drupal::requestStack()->getCurrentRequest();

    // Check if the webform has a remote address.
    if ($this->getWebform()->hasRemoteAddr()) {
      // Prefer 'X-Forwarded-For' header, but fall back to 'remote_addr' if it's not present.
      $ip_address = $request->headers->has('X-Forwarded-For')
        ? trim(explode(',', $request->headers->get('X-Forwarded-For'))[0])
        : $request->getClientIp();

      // Save the IP address value to the webform submission.
      $submission_data = $webform_submission->getData();
      $submission_data[$element['#webform_key']] = $ip_address;
      $webform_submission->setData($submission_data);

      return $ip_address;
    }

    // Return empty string if no IP address is available.
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    // This is an input element since it captures data.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return ['#markup' => $this->t('Displays the IP Address.')];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Custom settings for the IP address element can be added here.
    $form = parent::buildConfigurationForm($form, $form_state);
    return $form;
  }
}
