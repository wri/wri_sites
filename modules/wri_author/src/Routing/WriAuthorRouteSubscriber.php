<?php

namespace Drupal\wri_author\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds autocomplete behavior to the route.
 */
class WriAuthorRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\wri_author\Controller\EntityAutocompleteController::handleAutocomplete');
    }
  }

}
