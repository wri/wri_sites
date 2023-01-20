<?php

namespace Drupal\wri_common\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\StringArgument;

/**
 * Argument handler for strings with spaces.
 *
 * Basic argument handler to implement string arguments that may have length
 * limits.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("string_with_space")
 */
class StringWithSpaceArgument extends StringArgument {

  /**
   * {@inheritDoc}
   */
  public static function breakString($str, $force_int = FALSE) {
    // Change my spaces into another character and then change back.
    // Some day there may be a string with a # in it, but for now, this works.
    $splitter = '#';

    $str = str_replace(' ', $splitter, $str);
    $result = parent::breakString($str, $force_int);
    foreach ($result->value as $key => $value) {
      $result->value[$key] = str_replace($splitter, ' ', $value);
    }
    return $result;
  }

}
