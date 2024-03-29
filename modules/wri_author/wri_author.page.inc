<?php

/**
 * @file
 * Contains wri_author.page.inc.
 *
 * Page callback for Author entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Author templates.
 *
 * Default template: wri_author.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_wri_author(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
