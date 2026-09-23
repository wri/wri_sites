<?php

declare(strict_types=1);

namespace Drupal\wri_taxonomy;

/**
 * Constants for passing the current term through Views AJAX requests.
 *
 * @see wri_taxonomy_get_current_term()
 * @see wri_search_form_views_exposed_form_alter()
 */
final class CurrentTerm {

  /**
   * Name of the hidden exposed-filter field that carries the current term.
   */
  public const QUERY_PARAM = 'wri_current_term';

}
