<?php

namespace Drupal\wri_subpage\Plugin\DsField;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the terms from a chosen taxonomy vocabulary.
 *
 * @DsField(
 *   id = "childtitle",
 *   title = @Translation("Add Title"),
 *   entity_type = "node",
 *   ui_limit = {"project_detail|*"}
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
        $info['#markup'] = '<h1 class="intro-text">' . $title . '</h1>';
      }
    }

    return $info;
  }

}
