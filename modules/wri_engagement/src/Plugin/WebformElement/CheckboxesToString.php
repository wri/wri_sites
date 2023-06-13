<?php

namespace Drupal\wri_engagement\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\Checkboxes;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'checkboxes' element that saves the value as a string.
 *
 * @WebformElement(
 *   id = "checkboxes_string",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkboxes.php/class/Checkboxes",
 *   label = @Translation("Checkboxes to String"),
 *   description = @Translation("Provides a form element for a set of checkboxes that saves the value as a string."),
 *   category = @Translation("Options elements"),
 * )
 */
class CheckboxesToString extends Checkboxes {

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    $element['#type'] = 'checkboxes';
    parent::initialize($element);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $original_value = $webform_submission->getElementData($element['#webform_key']);
    $webform_submission->setElementData($element['#webform_key'], implode(',', $original_value));
  }

}
