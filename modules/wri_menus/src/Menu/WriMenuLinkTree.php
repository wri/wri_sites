<?php

namespace Drupal\wri_menus\Menu;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuTreeStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\PreloadableRouteProviderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Utility\CallableResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   */
  public function __construct(MenuTreeStorageInterface $tree_storage, MenuLinkManagerInterface $menu_link_manager, RouteProviderInterface $route_provider, MenuActiveTrailInterface $menu_active_trail, ControllerResolverInterface|CallableResolver $callable_resolver, \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($tree_storage, $menu_link_manager, $route_provider, $menu_active_trail, $callable_resolver);
    $this->entityTypeManager = $entity_type_manager;
  }
  /**
   * {@inheritdoc}
   */
  public function getCurrentRouteMenuTreeParameters($menu_name) {
    $parameters = parent::getCurrentRouteMenuTreeParameters($menu_name);

    // If the current route's url route name matches the menu link's route name
    // and route parameters, unset it. We are assuming no menu item has itself
    // as a parent.
    $route_name = \Drupal::routeMatch()->getRouteName();
    $route_parameters = \Drupal::routeMatch()->getRawParameters()->all();

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
