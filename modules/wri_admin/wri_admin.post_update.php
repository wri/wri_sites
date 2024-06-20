<?php

/**
 * @file
 * Functions for wri admin post_update hooks.
 */

use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;

/**
 * Updates body fields to use .bullets class instead of featured-content-list.
 */
function wri_admin_post_update_rewrite_bullets(&$sandbox) {
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = Drupal::database()->select('node__body', 'u')
      ->condition('u.body_value', '%featured-content-list%', 'LIKE')
      ->fields('u')
      ->countQuery()
      ->execute()
      ->fetchField();
    $sandbox['current'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $users_per_batch = 25;
  $start_value = $sandbox['current'];
  $nids = Drupal::database()->select('node__body', 'u')
    ->condition('u.body_value', '%featured-content-list%', 'LIKE')
    ->condition('entity_id', $sandbox['current'], '>=')
    ->fields('u')
    ->orderBy('entity_id')
    ->range(0, $users_per_batch)
    ->execute();

  if (empty($nids)) {
    $sandbox['#finished'] = 1;
    return;
  }
  // Loop through each node.
  foreach ($nids as $result) {
    $node = Node::load($result->entity_id);
    if ($node->hasTranslation($result->langcode)) {
      $node = $node->getTranslation($result->langcode);
    }
    $body_value = $node->body->getValue();
    // Replace link strings with new values.
    $body_value[0]['value'] = str_replace(
      ['callout featured-content-list',
      ],
      ['bullets border-small',
      ],
      $body_value[0]['value']
    );

    $dom = Html::load($body_value[0]['value']);
    $xpath = new \DOMXPath($dom);
    // Only set loading="lazy" if no existing loading attribute is specified and
    // dimensions are specified.
    foreach ($xpath->query('//div[contains(@class, "bullets")]//p') as $element) {
      // Create a div element.
      $div = $dom->createElement('div');
      // Put the contents of $element into the new $div.
      $div->nodeValue = htmlspecialchars($element->nodeValue);
      // Give the div a class of 'h4 bottom-border-tiny-black'.
      $div->setAttribute('class', 'h4 bottom-border-tiny-black');
      // Attach the div to $element's parent.
      $element->parentNode->insertBefore($div, $element);
      // Delete $element.
      $element->parentNode->removeChild($element);
    }
    $body_value[0]['value'] = Html::serialize($dom);
    $node->body->setValue($body_value);

    // Save the node.
    $node->save();
    $sandbox['current'] = $result->entity_id;
  }

  \Drupal::messenger()
    ->addMessage($sandbox['current'] . ' node last processed.');

  $sandbox['#finished'] = ($sandbox['current'] == $start_value);
}
