<?php

namespace Drupal\wri_common\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'tokenized_link_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "tokenized_link_field_widget",
 *   label = @Translation("Resource link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class TokenizedLinkFieldWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity = $items->getEntity();
    $use_default = TRUE;

    $tokenizedValue = \Drupal::token()->replace($element['uri']['#default_value'], [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
    // This indicates that no tokens are being replaced, so it's probably an
    // external url, or a custom overwrite, like /resources.
    if ($tokenizedValue == $element['uri']['#default_value']) {
      $use_default = FALSE;
    }
    if ($tokenizedValue) {
      try {
        $url = Url::fromUserInput($tokenizedValue, ['attributes' => ['target' => 'blank']]);
      }
      catch (\InvalidArgumentException $exception) {
        $use_default = FALSE;
        $url = Url::fromUri($tokenizedValue, ['attributes' => ['target' => 'blank']]);
      }
      $internal_link = Link::fromTextAndUrl($tokenizedValue, $url)->toString();
    }
    else {
      $internal_link = '';
    }

    $element['use_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate url from other fields.'),
      '#default_value' => $use_default,
      '#weight' => -1,
      '#description' => $this->t('If checked, the value of this link will be based on the value of other fields on this block. For example: @link', ['@link' => $internal_link]),
    ];

    // Selector logic pulled from the parent LinkWidget.
    $field_name = $this->fieldDefinition->getName();

    $parents = $element['#field_parents'];
    $parents[] = $field_name;
    $selector = $root = array_shift($parents);
    if ($parents) {
      $selector = $root . '[' . implode('][', $parents) . ']';
    }

    // Hide the uri if we're using the default.
    $element['uri']['#states'] = [
      'visible' => [
        ':input[name="' . $selector . '[' . $delta . '][use_default]"]' => ['checked' => FALSE],
      ],
    ];

    return $element;
  }

}
