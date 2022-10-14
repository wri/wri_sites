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
      'menu' => 'page-hierarchies',
      'color_class' => 'black-bar'
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['menu'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu name'),
      '#description' => $this->t('The dash-case name of the menu: ie page-hierarchies'),
      '#default_value' => $this->getSetting('menu') ?? 'page-hierarchies',
    ];
    $elements['color_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color class'),
      '#description' => $this->t('The background color class to apply. Example: black-bar, teal-bar'),
      '#default_value' => $this->getSetting('color_class') ?? 'black-bar',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Menu: @menu', ['@menu' => $this->getSetting('menu')]);
    $summary[] = $this->t('Class: @color_class', ['@color_class' => $this->getSetting('color_class')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $value = $menu_link = $node_title = '';
    foreach ($items as $delta => $item) {
      $value = $item->value;

      $node = \Drupal::routeMatch()->getParameter('node');
      if ($node instanceof \Drupal\node\NodeInterface) {
        $node_title = $node->getTitle();
      }
      // Get the menu link of the current node.
      if($value == 'menu' && $entity = $item->getEntity()) {
        $node_id = $item->getEntity()->id();
        if ($node_id) {
          $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
          $menu_link = array_key_first($menu_link_manager->loadLinksByRoute('entity.node.canonical', ['node' => $node_id], $this->getSetting('menu')));
        }
      }

    }

    return [
      '#theme' => 'table_of_contents',
      '#menu_title' => empty($this->getSetting('menu')) ? 'page-hierarchies' : $this->getSetting('menu'),
      '#value' => $value,
      '#menu_link' => $menu_link,
      '#node_title' => $node_title,
      '#color_bar' => $this->getSetting('color_class'),
    ];
  }

}
