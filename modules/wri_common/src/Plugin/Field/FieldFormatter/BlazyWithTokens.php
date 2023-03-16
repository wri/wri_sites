<?php

namespace Drupal\wri_common\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;

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
 * @deprecated Remove Blazy, then delete this plugin.
 */
class BlazyWithTokens extends BlazyMediaFormatter {

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
    $token_entities = [];
    $hide_if_empty = $this->getSetting('hide_if_token_empty');
    // If there's a value in the tokenized version of the default value,
    // use that, otherwise use the default value (or hide).
    foreach ($items as $item) {
      $entity = $item->getEntity();
      $token_entity = $this->getSetting('default_value');
      $token_entities = \Drupal::token()->replace($token_entity, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
      if ($token_entities) {
        $entities[] = Media::load($token_entities);
      }
    }

    if (empty($token_entities) && !$hide_if_empty) {
      $entities = $this->getEntitiesToView($items, $langcode);
    }

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    $render = $this->commonViewElements($items, $langcode, $entities);
    // If this token is based on "current-page", this value will vary by url.
    $render['#cache']['contexts'][] = 'url';
    return $render;
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
