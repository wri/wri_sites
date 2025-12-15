<?php

declare(strict_types=1);

namespace Drupal\wri_search\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class WriSearchRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
//    // Todo: add setting for this.
//    $views_search = $collection->get('view.search.results');
//    if ($views_search && $collection->get('google_json_api.settings')) {
//      $path = $views_search->getPath();
//      $route = $collection->get('search.view_google_json_api_search');
//      $route->setPath($path);
//    }
  }

}
