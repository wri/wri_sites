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
   * Kernel response event handler.
   */
  public function onKernelRequest(\Symfony\Component\HttpKernel\Event\RequestEvent $event): void {
    if ($this->routeMatch->getRouteName() == 'search.view_google_json_api_search') {
      // Get the config setting for wri_search.settings
      $config = \Drupal::config('wri_search.settings');
      // If the setting for enable_google is false, throw 404.
      if (!$config->get('enable_google')) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      }
    }
    elseif ($this->routeMatch->getRouteName() == 'search.view') {
      $config = \Drupal::config('wri_search.settings');
      // If the setting for enable_google is false, throw 404.
      if ($config->get('enable_google')) {
        // Redirect to search/all including the path.
        $request = $event->getRequest();
        $query = $request->getQueryString();
        $new_path = '/search/all';
        if ($query) {
          $new_path .= '?' . $query;
        }
        $response = new \Symfony\Component\HttpFoundation\RedirectResponse($new_path);
        $event->setResponse($response);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::VIEW => ['onKernelView',10],
      KernelEvents::REQUEST => ['onKernelRequest',10],
    ];
  }

}
