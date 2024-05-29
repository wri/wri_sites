<?php

namespace Drupal\wri_event\Plugin\DsField;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the registration button depending on Zoom settings.
 *
 * @DsField(
 *   id = "register_button",
 *   title = @Translation("Smart Register Button."),
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
      }
      else {
        $button_classes = ['button', 'small'];
      }
      $zoom_id = $entity->field_zoom_webinar_id->value;
      $webform_id = $entity->field_zoom_registration_form->target_id;
      $registration_link = $entity->field_register->uri;

      if ($zoom_id && $webform_id) {
        $node_url = $entity->toUrl();
        $node_url->setOption('fragment', 'webform-' . $webform_id);
        $link = new Link('Register', $node_url);

        $info['link'] = $link->toRenderable();
        $info['link']['#attributes'] = [
          'class' => $button_classes
        ];
      }
      elseif ($registration_link) {
        // Display the rendered register link.
        $url = Url::fromUri($registration_link);
        $link = new Link('Register', $url);

        $info['link'] = $link->toRenderable();
        $info['link']['#attributes'] = [
          'class' => $button_classes
        ];
      }
    }

    return $info;
  }

}
