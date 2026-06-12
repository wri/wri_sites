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
    $config_default = $items->getFieldDefinition()->getDefaultValue($items->getEntity());
    $use_default = TRUE;

    $config_default_tokenized_value = \Drupal::token()->replace($config_default[0]["uri"], [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
    $config_default_tokenized_link = Url::fromUri($config_default_tokenized_value)->toString();

    $user_submitted_default_value = $element['uri']['#default_value'];
    $user_submitted_tokenized_value = \Drupal::token()->replace($user_submitted_default_value, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);

    // This indicates that no tokens are being replaced, so it's probably an
    // external url, or a custom overwrite, like /resources.
    if ($user_submitted_tokenized_value != $config_default_tokenized_link) {
      $use_default = FALSE;
    }
    if ($user_submitted_default_value) {
      try {
        $url = Url::fromUserInput($user_submitted_default_value, ['attributes' => ['target' => 'blank']]);
      }
      catch (\InvalidArgumentException $exception) {
        try {
          $url = Url::fromUri($user_submitted_default_value, ['attributes' => ['target' => 'blank']]);
        }
        catch (\InvalidArgumentException $exception) {
          $url = Url::fromRoute($user_submitted_default_value, ['attributes' => ['target' => 'blank']]);
        }
      }
      $internal_link = Link::fromTextAndUrl($user_submitted_tokenized_value, $url)
        ->toString();
    }
    else {
      $url = Url::fromUserInput($config_default_tokenized_link, ['attributes' => ['target' => 'blank']]);
      $internal_link = Link::fromTextAndUrl($config_default_tokenized_link, $url)->toString();
    }

    // Determine the current link mode from stored values.
    if ($use_default) {
      $link_mode = 'default';
    }
    elseif (!empty($element['uri']['#default_value'])) {
      $link_mode = 'manual';
    }
    else {
      $link_mode = 'none';
      $element['uri']['#default_value'] = str_replace('internal:', '', $config_default[0]["uri"]);
      $element['title']['#default_value'] = $config_default[0]["title"];
    }

    $element['link_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Link type'),
      '#options' => [
        'default' => $this->t('Generate url from other fields'),
        'manual' => $this->t('Create link manually'),
        'none' => $this->t('No link'),
      ],
      '#default_value' => $link_mode,
      '#weight' => -1,
      '#description' => $this->t('"Generate url from other fields" bases this link on the value of other fields on this block. For example: @link', ['@link' => $internal_link]),
    ];

    // Selector logic pulled from the parent LinkWidget.
    $field_name = $this->fieldDefinition->getName();

    $parents = $element['#field_parents'];
    $parents[] = $field_name;
    $selector = $root = array_shift($parents);
    if ($parents) {
      $selector = $root . '[' . implode('][', $parents) . ']';
    }

    $link_mode_selector = ':input[name="' . $selector . '[' . $delta . '][link_mode]"]';

    // Show the uri only when manually entering a link.
    $element['uri']['#states'] = [
      'visible' => [
        $link_mode_selector => ['value' => 'manual'],
      ],
    ];

    if (isset($element['title'])) {
      // Hide the title field only when 'none' is selected.
      $element['title']['#states'] = [
        'invisible' => [
          $link_mode_selector => ['value' => 'none'],
        ],
      ];
      // Remove #required so server-side validation doesn't fire for hidden
      // elements. Required enforcement for manual mode is handled in
      // validateElement.
      $element['title']['#required'] = FALSE;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    $parents = $element['#parents'];
    array_pop($parents);
    $parents[] = 'link_mode';
    $link_mode = $form_state->getValue($parents);

    if ($link_mode === 'none') {
      $form_state->setValueForElement($element, '');
      return;
    }

    parent::validateUriElement($element, $form_state, $form);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if (isset($value['link_mode']) && $value['link_mode'] === 'none') {
        $value['uri'] = '';
        $value['title'] = '';
      }
      unset($value['link_mode']);
    }
    return parent::massageFormValues($values, $form, $form_state);
  }

}
