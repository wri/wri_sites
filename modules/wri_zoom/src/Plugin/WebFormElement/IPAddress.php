<?php

namespace Drupal\wri_zoom\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

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
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $data = $webform_submission->getData();
    $data[$element['#webform_key']] = $webform_submission->getRemoteAddr();
    $webform_submission->setData($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $webform_submission->getRemoteAddr();
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
