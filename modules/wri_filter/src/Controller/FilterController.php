<?php

namespace Drupal\wri_filter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Manages setting the site-wide filter.
 */
class FilterController extends ControllerBase {

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempstorePrivate;


  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The WRI filter service.
   *
   * @var \Drupal\wri_filter\FilterService
   */
  protected $filterService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentRouteMatch = $container->get('current_route_match');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->filterService = $container->get('wri_filter.filter');
    return $instance;
  }

  /**
   * Apply filter.
   *
   * @param string $filter
   *   The filter to be applied.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Status and filter.
   */
  public function applyFilter($filter) {
    $cookie = new Cookie($this->filterService->getFilterCookieName(), $filter, 0, '/', NULL, FALSE, FALSE);
    $response = new Response();
    $response->headers->setCookie($cookie);
    $response->send();

    return new JsonResponse([
      'data' => ['status' => '200', 'filter' => $filter],
      'method' => 'GET',
    ]);
  }

  /**
   * Remove filter.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Status and filter.
   */
  public function removeFilter() {
    $cookie = new Cookie($this->filterService->getFilterCookieName(), '', -3600, '/', NULL, FALSE, FALSE);
    $response = new Response();
    $response->headers->setCookie($cookie);
    $response->send();

    return new JsonResponse([
      'data' => ['status' => '200'],
      'method' => 'GET',
    ]);
  }

}
