<?php

declare(strict_types=1);

namespace Drupal\wri_spoke\Service;

use Drupal\entity_share_client\Service\FormHelper;
use Drupal\Core\Url;

/**
 * Service to extract code out of the PullForm.
 *
 * @package Drupal\entity_share_client\Service
 */
class HubFormHelper extends FormHelper {

  /**
   * {@inheritdoc}
   */
  protected function getOptionLabel(array $data, array $status_info, array $entity_keys, $remote_url, $level) {
    // It's confusing that on our spokes, "View local" points to the hub.
    // Set it to instead point to the local node edit page.
    if (isset($status_info['local_entity_link'])) {
      $status_info['local_entity_link'] = Url::fromRoute('entity.node.edit_form', $status_info['local_entity_link']->getRouteParameters());
    }

    $label = parent::getOptionLabel($data, $status_info, $entity_keys, $remote_url, $level);

    return $label;
  }

}
