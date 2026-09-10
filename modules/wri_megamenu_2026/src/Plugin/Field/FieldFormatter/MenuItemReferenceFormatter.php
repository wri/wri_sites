<?php

declare(strict_types=1);

namespace Drupal\wri_megamenu_2026\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\twig_tweak\View\BlockViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'menu_item_reference_label' formatter.
 *
 * Renders the mega menu button and its flyout panel for the referenced menu
 * item: the button and heading show the menu item's title, and the flyout
 * panel contains the paragraph's field_text and field_listing plus a
 * menu_block showing the menu item's children.
 *
 * Replaces the previous submenu-specific block in
 * ts_wrin_preprocess_paragraph() and the hand-built markup in
 * paragraph--submenu--default.html.twig.
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
    protected readonly BlockViewBuilder $blockViewBuilder,
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
      $container->get('twig_tweak.block_view_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    $paragraph = $items->getEntity();

    foreach ($items as $delta => $item) {
      $menu_item = $item->entity;
      if (!$menu_item instanceof MenuLinkContentInterface) {
        continue;
      }

      $elements[$delta] = [
        '#theme' => 'wri_megamenu_2026_submenu',
        '#title' => $menu_item->getTitle(),
        '#text' => $this->buildSiblingField($paragraph, 'field_text'),
        '#listing' => $this->buildSiblingField($paragraph, 'field_listing'),
        '#menu_block' => $this->buildMenuBlock($menu_item),
        '#cache' => [
          'tags' => $menu_item->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

  /**
   * Renders another field on the same paragraph, using its own display.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph this formatter's field belongs to.
   * @param string $field_name
   *   The sibling field to render.
   *
   * @return array|null
   *   The rendered field, or NULL if the paragraph has no such field.
   */
  protected function buildSiblingField(ParagraphInterface $paragraph, string $field_name): ?array {
    if (!$paragraph->hasField($field_name)) {
      return NULL;
    }

    return $paragraph->get($field_name)->view($this->viewMode);
  }

  /**
   * Builds the menu_block render array for a menu item's children.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_item
   *   The referenced menu item; its children are shown in the flyout panel.
   *
   * @return array
   *   The menu_block's render array.
   */
  protected function buildMenuBlock(MenuLinkContentInterface $menu_item): array {
    $menu_name = $menu_item->getMenuName();
    $menu = $this->entityTypeManager->getStorage('menu')->load($menu_name);

    $plugin_id = 'menu_block:' . $menu_name;

    return $this->blockViewBuilder->build($plugin_id, [
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
    ]);
  }

}
