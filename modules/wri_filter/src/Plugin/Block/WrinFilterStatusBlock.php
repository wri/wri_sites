<?php

namespace Drupal\wri_filter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a wrin search block.
 *
 * @Block(
 *   id = "single_file_component_block:wri_filter_status",
 *   admin_label = @Translation("WRIN Filter Status"),
 *   category = @Translation("Custom")
 * )
 */
class WrinFilterStatusBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['cookies:STYXKEY_wri_filter']);
  }

}
