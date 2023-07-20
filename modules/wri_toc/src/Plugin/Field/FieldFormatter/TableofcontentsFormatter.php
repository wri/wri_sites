<?php

namespace Drupal\wri_toc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class TableofcontentsFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $linkManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RouteMatchInterface $route_match, MenuLinkManagerInterface $menu_link_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->routeMatch = $route_match;
    $this->linkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_route_match'),
      $container->get('plugin.manager.menu.link'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'menu' => 'page-hierarchies',
      'color_class' => 'black-bar',
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
    foreach ($items as $item) {
      $value = $item->value;

      $node = $this->routeMatch->getParameter('node');
      if ($node instanceof NodeInterface) {
        $node_title = $node->getTitle();
      }
      // Get the menu link of the current node.
      if ($value != 1 && $item->getEntity()) {
        $node_id = $item->getEntity()->id();
        if ($node_id) {
          $menu_link_manager = $this->linkManager;
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
