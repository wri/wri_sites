<?php

namespace Drupal\wri_filter\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a wrin search block.
 *
 * @Block(
 *   id = "single_file_component_block:wri_filter",
 *   admin_label = @Translation("WRIN Filter"),
 *   category = @Translation("Custom")
 * )
 */
class WrinFilterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [];
    return $build;
  }

}
