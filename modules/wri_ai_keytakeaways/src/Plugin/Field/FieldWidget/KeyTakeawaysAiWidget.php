<?php

declare(strict_types=1);

namespace Drupal\wri_ai_keytakeaways\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget;

/**
 * Field widget for Key Takeaways — delegates AI button to hook_form_alter.
 *
 * @FieldWidget(
 *   id = "key_takeaways_ai_widget",
 *   label = @Translation("Key Takeaways with AI"),
 *   field_types = {"text_long"}
 * )
 */
class KeyTakeawaysAiWidget extends TextareaWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    return parent::formElement($items, $delta, $element, $form, $form_state);
  }

}
