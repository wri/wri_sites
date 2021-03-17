<?php

namespace Drupal\wri_admin\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\viewfield\Plugin\Field\FieldFormatter\ViewfieldFormatterDefault;

/**
 * Viewfield Default Formatter plugin definition.
 *
 * @FieldFormatter(
 *   id = "viewfield_token",
 *   label = @Translation("Viewfield with Tokens"),
 *   field_types = {"viewfield"}
 * )
 */
class ViewfieldFormatterToken extends ViewfieldFormatterDefault {

  /**
   * {@inheritDoc}
   */
  protected function processArguments($argument_string, FieldableEntityInterface $entity) {
    // Quick fix for 255 characters for the arguments field.
    $argument_string = str_replace('[bc:', '[block_content:', $argument_string);
    $arguments = parent::processArguments($argument_string, $entity);

    // If the token failed to find anything, pass "all" to the view.
    $token_service = \Drupal::token();
    $token_data = [$entity->getEntityTypeId() => $entity];
    foreach ($arguments as $key => $value) {
      $arguments[$key] = trim($token_service->replace($value, $token_data, ['clear' => TRUE]), '+');
      if (empty($arguments[$key])) {
        $arguments[$key] = 'all';
      }
    }
    return $arguments;
  }

}
