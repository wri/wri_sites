<?php

namespace Drupal\wri_block\Plugin\DsField\Block;

use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the title of a block.
 *
 * @DsField(
 *   id = "block_label",
 *   title = @Translation("Layout Block Label"),
 *   entity_type = "block_content",
 *   provider = "block_content"
 * )
 */
class BlockLabel extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // This needs to be added at the preprocess block level. This just holds
    // the place.
    return [
      '#markup' => $this->entity()->info->value,
    ];
  }

}
