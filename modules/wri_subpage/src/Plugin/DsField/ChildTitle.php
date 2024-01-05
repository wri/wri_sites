<?php

namespace Drupal\wri_subpage\Plugin\DsField;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\node\NodeInterface;

/**
 * Plugin that renders the terms from a chosen taxonomy vocabulary.
 *
 * @DsField(
 *   id = "childtitle",
 *   title = @Translation("Add Title"),
 *   entity_type = "node",
 *   ui_limit = {"project_detail|*","microsite|*","publication|*"}
 * )
 */
class ChildTitle extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $info = [];

    $request = \Drupal::request();
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $title = \Drupal::service('title_resolver')->getTitle($request, $route);
      if (!is_null($title)) {
        $info['output'] = [
          '#type' => 'html_tag',
          '#tag' => 'h1',
          '#attributes' => ['class' => 'intro-text'],
          '#value' => $title,
        ];
      }
    }

    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node && ($node instanceof NodeInterface) && isset($node->field_long_title->value)) {
      $info['output'] = [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#attributes' => ['class' => 'intro-text'],
        'child' => $node->field_long_title->view(['label' => 'hidden']),
      ];
    }

    return $info;
  }

}
