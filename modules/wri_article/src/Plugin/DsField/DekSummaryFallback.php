<?php

namespace Drupal\wri_article\Plugin\DsField;

use Drupal\Core\Link;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the registration button depending on Zoom settings.
 *
 * @DsField(
 *   id = "dek_summary_fallback",
 *   title = @Translation("Dek with body summary fallback"),
 *   entity_type = "node",
 *   ui_limit = {"article|*"}
 * )
 */
class DekSummaryFallback extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->configuration['entity'];
    $info = [];

    if (isset($entity->field_intro->value)) {
      $render = $entity->field_intro->view(array('type' => 'text_default', 'label' => 'hidden'));
      $info = ['#markup' => \Drupal::service('renderer')->render($render)];
    }
    elseif (isset($entity->body->summary) && !empty($entity->body->summary)) {
      $info = ['#markup' => '<div class="clearfix text-formatted field field--name-field-intro field--type-text-long field--label-hidden field__item"><p>' . $entity->body->summary . '</p></div>'];
    }

    return $info;
  }

}
