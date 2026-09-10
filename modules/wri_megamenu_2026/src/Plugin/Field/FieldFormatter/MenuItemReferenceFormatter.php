<?php

declare(strict_types=1);

namespace Drupal\wri_megamenu_2026\Plugin\Field\FieldFormatter;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'menu_item_reference_label' formatter.
 *
 * Renders the menu_block content for the referenced menu item's children
 * (the mega menu flyout panel's link tree). The button, heading, and the
 * paragraph's field_text/field_listing are handled elsewhere: the button and
 * heading read the referenced item's title directly in
 * paragraph--submenu--default.html.twig, and field_text/field_listing keep
 * rendering through their own formatters as configured on the paragraph's
 * display.
 *
 * Builds the menu_block plugin and renders it the same way
 * \Drupal\menu_block\Plugin\Block\MenuBlock::build() output is normally
 * wrapped for display, without going through a block-rendering helper
 * module.
 *
 * @FieldFormatter(
 *   id = "menu_item_reference_label",
 *   label = @Translation("Mega menu submenu"),
 *   field_types = {
 *     "menu_item_reference"
 *   }
 * )
 */
final class MenuItemReferenceFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a MenuItemReferenceFormatter.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly BlockManagerInterface $blockManager,
    protected readonly AccountInterface $currentUser,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.block'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $menu_item = $item->entity;
      if (!$menu_item instanceof MenuLinkContentInterface) {
        continue;
      }

      $elements[$delta] = $this->buildMenuBlock($menu_item);
    }

    return $elements;
  }

  /**
   * Builds and renders the menu_block for a menu item's children.
   *
   * Mirrors how core builds and wraps a block for display (see
   * \Drupal\Core\Block\BlockViewBuilder / block.html.twig), so the flyout
   * panel keeps the same markup (including the
   * "block-menu-blockMENU_NAME"-prefixed wrapper ID) that
   * ts_megamenu.js/ts_megamenu_mobile.js select via :scope queries.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_item
   *   The referenced menu item; its children are shown in the flyout panel.
   *
   * @return array
   *   The render array for the menu_block, or an empty array if access is
   *   denied or the block has no content.
   */
  protected function buildMenuBlock(MenuLinkContentInterface $menu_item): array {
    $menu_name = $menu_item->getMenuName();
    $menu = $this->entityTypeManager->getStorage('menu')->load($menu_name);
    $plugin_id = 'menu_block:' . $menu_name;

    $configuration = [
      'id' => $plugin_id,
      'label' => $menu?->label() ?? $menu_name,
      'label_display' => 0,
      'provider' => 'menu_block',
      'follow' => 1,
      'follow_parent' => 'child',
      'display_empty' => FALSE,
      'label_link' => FALSE,
      'label_type' => 'block',
      'level' => 1,
      'depth' => 0,
      'expand_all_items' => TRUE,
      'parent' => $menu_name . ':menu_link_content:' . $menu_item->uuid(),
      'render_parent' => FALSE,
      'suggestion' => str_replace('-', '_', $menu_name),
      'hide_on_nonactive' => FALSE,
    ];

    /** @var \Drupal\Core\Block\BlockPluginInterface $block_plugin */
    $block_plugin = $this->blockManager->createInstance($plugin_id, $configuration);

    $access = $block_plugin->access($this->currentUser, TRUE);
    if (!$access->isAllowed()) {
      $build = [];
      CacheableMetadata::createFromRenderArray($build)
        ->addCacheableDependency($access)
        ->addCacheableDependency($block_plugin)
        ->addCacheableDependency($menu_item)
        ->applyTo($build);
      return $build;
    }

    $content = $block_plugin->build();
    if (!is_array($content) || Element::isEmpty($content)) {
      $build = [];
      CacheableMetadata::createFromRenderArray($build)
        ->addCacheableDependency($access)
        ->addCacheableDependency($block_plugin)
        ->addCacheableDependency($menu_item)
        ->applyTo($build);
      return $build;
    }

    $build = ['content' => $content];
    $build += [
      '#theme' => 'block',
      '#id' => $plugin_id,
      '#attributes' => [],
      '#contextual_links' => [],
      '#configuration' => $block_plugin->getConfiguration(),
      '#plugin_id' => $block_plugin->getPluginId(),
      '#base_plugin_id' => $block_plugin->getBaseId(),
      '#derivative_plugin_id' => $block_plugin->getDerivativeId(),
    ];
    // Semantically, #attributes and #contextual_links describe the *entire*
    // block, so move them from the plugin's content to the top level.
    foreach (['#attributes', '#contextual_links'] as $property) {
      if (isset($build['content'][$property])) {
        $build[$property] = $build['content'][$property];
        unset($build['content'][$property]);
      }
    }

    CacheableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($access)
      ->addCacheableDependency($block_plugin)
      ->addCacheableDependency($menu_item)
      ->applyTo($build);

    return $build;
  }

}
