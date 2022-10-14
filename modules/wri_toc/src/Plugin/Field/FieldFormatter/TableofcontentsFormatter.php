<?php

namespace Drupal\wri_toc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TableOfContents' formatter.
 *
 * @FieldFormatter(
 *   id = "single_file_component_field_formatter:toc_formatter",
 *   label = @Translation("Table of Contents"),
 *   field_types = {
 *     "list_string"
 *   }
 * )
 */
class TableofcontentsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'menu' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['menu'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu name'),
      '#default_value' => $this->getSetting('menu'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Menu: @menu', ['@foo' => $this->getSetting('menu')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $item->value,
      ];
    }

    return $element;
  }

}
