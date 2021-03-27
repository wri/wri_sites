<?php

namespace Drupal\wri_common\Plugin\DsField\Block;

use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Plugin that renders the child menu items of the page.
 *
 * @DsField(
 *   id = "child_pages",
 *   title = @Translation("Child pages"),
 *   entity_type = "node"
 * )
 */
class ChildPages extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $to_return = [];
    $node_id = $this->entity()->id();
    if ($node_id) {
      $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
      $menu_link = $menu_link_manager->loadLinksByRoute('entity.node.canonical', ['node' => $node_id], 'page-hierarchies');
      if ($menu_link) {
        // Load the menu entity from the link.
        $current_link_menu = MenuLinkContent::load(current($menu_link)->getPluginDefinition()["metadata"]["entity_id"]);
        $to_return = ['#menu_link' => \array_key_first($menu_link)];
        // All code done in the twig template.
        if ($parent = $current_link_menu->getParentId()) {
          $to_return = [
            '#menu_link' => $parent,
          ];
        }
      }
    }
    return $to_return;
  }

}
