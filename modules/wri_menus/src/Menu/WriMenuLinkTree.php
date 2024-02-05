<?php

namespace Drupal\wri_menus\Menu;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Menu\MenuTreeStorageInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Utility\CallableResolver;

/**
 * Implements the loading, transforming and rendering of menu link trees.
 */
class WriMenuLinkTree extends MenuLinkTree {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match object for the current page.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a \Drupal\Core\Menu\MenuLinkTree object.
   *
   * @param \Drupal\Core\Menu\MenuTreeStorageInterface $tree_storage
   *   The menu link tree storage.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link plugin manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   * @param \Drupal\Core\Utility\CallableResolver|\Drupal\Core\Controller\ControllerResolverInterface $callable_resolver
   *   The callable resolver.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   A route match object for finding the active link.
   */
  public function __construct(MenuTreeStorageInterface $tree_storage, MenuLinkManagerInterface $menu_link_manager, RouteProviderInterface $route_provider, MenuActiveTrailInterface $menu_active_trail, ControllerResolverInterface|CallableResolver $callable_resolver, EntityTypeManagerInterface $entity_type_manager, RouteMatch $current_route_match) {
    parent::__construct($tree_storage, $menu_link_manager, $route_provider, $menu_active_trail, $callable_resolver);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRouteMenuTreeParameters($menu_name) {
    $parameters = parent::getCurrentRouteMenuTreeParameters($menu_name);

    // Don't bother doing anything for top-level or second-level items.
    if (count($parameters->activeTrail) < 3) {
      return $parameters;
    }

    // If the current route's url route name matches the menu link's route name
    // and route parameters, unset it. We are assuming no menu item has itself
    // as a parent.
    $route_name = $this->routeMatch->getRouteName();
    $route_parameters = $this->routeMatch->getRawParameters()->all();

    foreach ($parameters->activeTrail as $key => $active_trail) {
      // Load the menu link from the active trail.
      $uuid = str_replace('menu_link_content:', '', $active_trail);
      $menu_link = $this->entityTypeManager->getStorage('menu_link_content')->loadByProperties(['uuid' => $uuid]);
      $menu_link = current($menu_link);
      if ($menu_link) {
        $url = $menu_link->getUrlObject();
        if ($url->getRouteName() == $route_name && $url->getRouteParameters() == $route_parameters) {
          unset($parameters->activeTrail[$key]);
          break;
        }
      }
    }

    return $parameters;
  }

}
