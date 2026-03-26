<?php

declare(strict_types = 1);

namespace Drupal\wri_spoke\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\entity_share\EntityShareUtility;
use Drupal\entity_share_client\Entity\RemoteInterface;
use Drupal\entity_share_client\Exception\ResourceTypeNotFoundException;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

/**
 * Service to extract code out of the PullForm.
 *
 * @package Drupal\entity_share_client\Service
 */
class HubFormHelper extends \Drupal\entity_share_client\Service\FormHelper {

  /**
   * { inheritdoc }
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
