<?php

declare(strict_types=1);

namespace Drupal\wri_search\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @todo Add description for this subscriber.
 */
final class WriSearchControllerAlterSubscriber implements EventSubscriberInterface {
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;
  /**
   * Constructs a new RouteCacheContext class.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Kernel response event handler.
   */
  public function onKernelView(ViewEvent $event): void {
    if ($this->routeMatch->getRouteName() == 'search.view_google_json_api_search') {
      $build = $event->getControllerResult();
      // Add wrapper around search_results.
      $build['#theme'] = 'wri_search_results_wrapper';
      $build['#pager'] = $build['pager'] ?? [];
      $build['#search_form'] = $build['search_form'] ?? [];
      $build['#search_results'] = $build['search_results'] ?? [];
      if (isset($build['search_results_title'])) {
        $build['#search_results_title'] = $build['search_results_title']['#markup'] ? strip_tags($build[ 'search_results_title']['#markup']) : '';
      }
      $event->setControllerResult($build);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::VIEW => ['onKernelView',10],
    ];
  }

}
