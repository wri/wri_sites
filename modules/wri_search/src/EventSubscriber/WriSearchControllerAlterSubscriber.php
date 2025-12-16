<?php

declare(strict_types=1);

namespace Drupal\wri_search\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Alters routes related to the search pages to account for google_cse.
 */
final class WriSearchControllerAlterSubscriber implements EventSubscriberInterface {
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a new RouteCacheContext class.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *  The config factory.
   */
  public function __construct(RouteMatchInterface $route_match, configFactoryInterface $config_factory) {
    $this->routeMatch = $route_match;
    $this->config = $config_factory->get('wri_search.settings');
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
        $build['#search_results_title'] = $build['search_results_title']['#markup'] ? strip_tags($build['search_results_title']['#markup']) : '';
      }
      $event->setControllerResult($build);
    }
  }

  /**
   * Kernel response event handler.
   */
  public function onKernelRequest(RequestEvent $event): void {
    if ($this->routeMatch->getRouteName() == 'search.view_google_json_api_search') {
      // If the setting for enable_google is false, throw 404.
      if (!$this->config->get('enable_google')) {
        throw new NotFoundHttpException();
      }
    }
    elseif ($this->routeMatch->getRouteName() == 'search.view') {
      // If the setting for enable_google is false, throw 404.
      if ($this->config->get('enable_google')) {
        // Redirect to search/all including the path.
        $request = $event->getRequest();
        $query = $request->getQueryString();
        $new_path = '/search/all';
        if ($query) {
          $new_path .= '?' . $query;
        }
        $response = new RedirectResponse($new_path);
        $event->setResponse($response);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::VIEW => ['onKernelView', 10],
      KernelEvents::REQUEST => ['onKernelRequest', 10],
    ];
  }

}
