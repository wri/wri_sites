<?php

namespace Drupal\wri_common\Plugin\DsField\Block;

use Drupal\ds\Plugin\DsField\DsFieldBase;

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
    $node_id = $this->entity()->id();
    if ($node_id) {
      $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
      $menu_link = array_key_first($menu_link_manager->loadLinksByRoute('entity.node.canonical', ['node' => $node_id], 'page-hierarchies'));
      if ($menu_link) {
        // All code done in the twig template.
        return [
          '#markup' => '',
        ];
      }
    }
    return [];
  }

}
