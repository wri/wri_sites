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
    $token_service = \Drupal::token();
    // First grab the content snippet value (which is the token stored in the field).
    $argument_string = $token_service->replace($argument_string, [], ['clear' => TRUE]);
    $arguments = parent::processArguments($argument_string, $entity);

    // If the token failed to find anything, pass "all" to the view.
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
