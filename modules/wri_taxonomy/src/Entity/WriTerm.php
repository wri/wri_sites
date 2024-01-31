<?php

namespace Drupal\wri_taxonomy\Entity;

use Drupal\taxonomy\Entity\Term;

/**
 * Extends the taxonomy term to slightly overwrite the toUrl method.
 */
class WriTerm extends Term {

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = NULL, array $options = []) {
    if ($rel === NULL) {
      $rel = 'canonical';
    }
    return parent::toUrl($rel, $options);
  }

}
