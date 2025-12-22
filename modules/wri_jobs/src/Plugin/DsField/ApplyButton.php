<?php

namespace Drupal\wri_jobs\Plugin\DsField;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the registration button depending on Zoom settings.
 *
 * @DsField(
 *   id = "apply_button",
 *   title = @Translation("Smart Apply Button"),
 *   entity_type = "node",
 *   ui_limit = {"jobs|*"}
 * )
 */
class ApplyButton extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->configuration['entity'];
    $info = [];

    if (isset($entity->field_job_end_date->value)) {
      $end = $entity->field_job_end_date->value;
    }

    if (isset($entity->field_job_is_closed_manual_optio->value)) {
      $closed = $entity->field_job_is_closed_manual_optio->value;
    }

    if (isset($entity->field_job_posting_link->uri)) {
      $url = $entity->field_job_posting_link->uri;
    }

    // If this event is in the past, show no button.
    if (!($end || $end > time()) && !$closed) {
      if ($this->viewMode() == 'main_content' || $this->viewMode() == 'full') {
        $button_classes = ['button', 'button--primary'];
      }
      else {
        $button_classes = ['button', 'small'];
      }

      if ($url) {
        $url = \Drupal\Core\Url::fromUri($url);
        $link = new Link($this->t('Apply'), $url);
        $info['link'] = $link->toRenderable();
        $info['link']['#attributes'] = [
          'class' => $button_classes,
        ];
      }
    } else {
      $info['#markup'] = '<h3>' . $this->t('The application deadline for this job posting has passed.') . '</h3>';
    }

    return $info;
  }

}
