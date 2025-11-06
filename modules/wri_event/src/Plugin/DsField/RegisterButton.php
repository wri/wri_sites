<?php

namespace Drupal\wri_event\Plugin\DsField;

use Drupal\Core\Link;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the registration button depending on Zoom settings.
 *
 * @DsField(
 *   id = "register_button",
 *   title = @Translation("Smart Register Button"),
 *   entity_type = "node",
 *   ui_limit = {"event|*"}
 * )
 */
class RegisterButton extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->configuration['entity'];
    $info = [];

    if (isset($entity->field_date_time->end_value)) {
      $end = $entity->field_date_time->end_value;
    }

    // If this event is in the past, show no button.
    if ($end > time()) {
      if ($this->viewMode() == 'main_content') {
        $button_classes = ['button', 'button--primary'];
        $url = wri_common_register_link($entity, FALSE);
      }
      else {
        $button_classes = ['button', 'small'];
        $url = wri_common_register_link($entity);
      }

      if ($url) {
        $link = new Link($this->t('Register'), $url);
        $info['link'] = $link->toRenderable();
        $info['link']['#attributes'] = [
          'class' => $button_classes,
        ];
      }
    }

    return $info;
  }

}
