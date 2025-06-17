<?php

namespace Drupal\wri_event\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\Field\FieldWidget\WebformEntityReferenceSelectWidget;

/**
 * Plugin implementation of the 'webform_entity_reference_select' widget.
 *
 * @FieldWidget(
 *   id = "registration_webform_entity_select",
 *   label = @Translation("Event Reg Select List"),
 *   description = @Translation("A select menu field widget that only lists Registration Webforms."),
 *   field_types = {
 *     "webform"
 *   }
 * )
 *
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase
 */
class RegistrationWebformSelectWidget extends WebformEntityReferenceSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function getTargetIdElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $target_element = parent::getTargetIdElement($items, $delta, $element, $form, $form_state);
    foreach ($target_element["#options"] as $webform_id => $value) {
      if (Webform::load($webform_id)->getElement('first_name') === NULL) {
        unset($target_element["#options"][$webform_id]);
      }
    }
    return $target_element;
  }
}
