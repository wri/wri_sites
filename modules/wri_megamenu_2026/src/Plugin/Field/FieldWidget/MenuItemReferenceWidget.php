<?php

declare(strict_types=1);

namespace Drupal\wri_megamenu_2026\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'menu_item_reference_select' widget.
 *
 * Shows all menu items in the field's configured target menu, in the same
 * order they are displayed on the menu's admin edit page.
 *
 * @FieldWidget(
 *   id = "menu_item_reference_select",
 *   label = @Translation("Menu item select list"),
 *   field_types = {
 *     "menu_item_reference"
 *   }
 * )
 */
final class MenuItemReferenceWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a MenuItemReferenceWidget.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected readonly MenuLinkTreeInterface $menuLinkTree,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $configuration['third_party_settings'],
      $container->get('menu.link_tree'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $menu_name = $this->getFieldSetting('target_menu');

    $options = ['' => $this->t('- Select -')];
    if ($menu_name) {
      $options += $this->getMenuOptions($menu_name);
    }

    $element['target_id'] = $element + [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $items[$delta]->target_id ?? '',
    ];

    return $element;
  }

  /**
   * Builds a flat, ordered list of selectable menu items for one menu.
   *
   * Mirrors \Drupal\menu_ui\MenuForm::buildOverviewForm() so the order here
   * matches the order shown on the menu's "Edit menu" page.
   *
   * @param string $menu_name
   *   The menu to load items from.
   *
   * @return string[]
   *   Menu link content entity IDs, keyed the same way, indented by depth.
   */
  protected function getMenuOptions(string $menu_name): array {
    $tree = $this->menuLinkTree->load($menu_name, new MenuTreeParameters());

    $manipulators = [
      ['callable' => 'menu_ui.menu_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    $options = [];
    $this->flattenMenuTree($tree, $options);
    return $options;
  }

  /**
   * Recursively flattens a menu tree into indented select list options.
   *
   * Only links backed by a menu_link_content entity are selectable; links
   * provided by modules (with no entity to reference) are skipped.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The (sub)tree to flatten.
   * @param string[] $options
   *   The options array to add to, passed by reference.
   */
  protected function flattenMenuTree(array $tree, array &$options): void {
    foreach ($tree as $element) {
      assert($element instanceof MenuLinkTreeElement);
      $definition = $element->link->getPluginDefinition();
      $entity_id = $definition['metadata']['entity_id'] ?? NULL;

      if ($entity_id) {
        $indent = $element->depth > 1 ? str_repeat('-- ', $element->depth - 1) : '';
        $options[$entity_id] = $indent . $element->link->getTitle();
      }

      if ($element->subtree) {
        $this->flattenMenuTree($element->subtree, $options);
      }
    }
  }

}
