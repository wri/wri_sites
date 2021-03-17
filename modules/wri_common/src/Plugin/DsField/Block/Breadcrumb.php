<?php

namespace Drupal\wri_common\Plugin\DsField\Block;

use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the breadcrumb the current page.
 *
 * @DsField(
 *   id = "breadcrumb",
 *   title = @Translation("Breadcrumb"),
 *   entity_type = "node"
 * )
 */
class Breadcrumb extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // All code done in the twig template.
    return [
      '#markup' => '',
    ];
  }

}
