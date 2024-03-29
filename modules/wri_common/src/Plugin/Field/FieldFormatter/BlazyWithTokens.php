<?php

namespace Drupal\wri_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'blazy_with_tokens' widget.
 *
 * @FieldFormatter(
 *   id = "blazy_with_tokens",
 *   label = @Translation("Blazy with tokens"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 *
 * @deprecated in drupal:9.1.4 and is removed from drupal:9.1.5. Remove Blazy, then delete this plugin.
 * @see https://www.drupal.org/node/0
 * @see https://github.com/wri/wri_sites/pull/149
 */
class BlazyWithTokens extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['default_value'] = '';
    $settings['hide_if_token_empty'] = TRUE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['default_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default display token'),
      '#size' => 100,
      '#description' => $this->t("Enter a string or token that will be used to render this field if the value is empty."),
      '#default_value' => $this->getSetting('default_value'),
    ];

    $form['hide_if_token_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide if token produces no output.'),
      '#description' => $this->t("If there is no image found in the token, hide the field. Otherwise, the fallback image will be used."),
      '#default_value' => $this->getSetting('hide_if_token_empty'),
    ];

    return $form;
  }

}
