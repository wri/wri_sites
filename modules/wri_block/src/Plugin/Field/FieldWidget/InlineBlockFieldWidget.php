<?php

namespace Drupal\wri_block\Plugin\Field\FieldWidget;

use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormSimple;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allows blocks to be saved with duplicate titles.
 *
 * @FieldWidget(
 *   id = "inline_block_field_widget",
 *   label = @Translation("Inline Block"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = false
 * )
 */
class InlineBlockFieldWidget extends InlineEntityFormSimple {

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (isset($values[0]['entity']) && $values[0]['entity']->getEntityTypeId() == 'block_content') {
      // Clear out errors about a duplicate block name.
      $tmp = $form_state->getErrors();
      $form_state->clearErrors();

      foreach ($tmp as $name => $error) {
        $error_arguments = $error->getArguments();
        if (($error->getUntranslatedString() == 'A @entity_type with @field_name %value already exists.') && ($error_arguments['@entity_type'] == 'custom block') && ($error_arguments['@field_name'] == 'block description')) {
          // Skip these.
        }
        else {
          $form_state->setErrorByName($name, $error);
        }
      }
    }
    return $values;
  }

}
