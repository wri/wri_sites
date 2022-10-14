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
      '#description' => $this->t('The dash-case name of the menu: ie page-hierarchies'),
      '#default_value' => $this->getSetting('menu') ?? 'page-hierarchies',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Menu: @menu', ['@menu' => $this->getSetting('menu')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $variables['menu_link'] = '';
      $variables['menu_title'] = $this->getSetting('menu') ?? 'page-hierarchies';
      $node = \Drupal::routeMatch()->getParameter('node');
      if ($node instanceof \Drupal\node\NodeInterface) {
        $variables['node_title'] = $node->getTitle();
      }
      // Get the menu link of the current node.
      if($item->value == 'menu' && $entity = $item->getEntity()) {
        $node_id = $item->getEntity()->id();
        if ($node_id) {
          $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
          $variables['menu_link'] = array_key_first($menu_link_manager->loadLinksByRoute('entity.node.canonical', ['node' => $node_id], $this->getSetting('menu')));
        }
      }
      $element[$delta] = [
        '#theme' => 'table_of_contents',
        '#elements' => $variables
      ];
    }


    return $element;
  }

}
