<?php

/**
 * @file
 * Contains \Drupal\field_permissions\Routing\RouteSubscriberFieldPermissions
 */

namespace Drupal\field_permissions\Routing;

use Drupal\Core\field_ui;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
* Listens to the dynamic route events.
*/
class RouteSubscriberFieldPermissions extends RouteSubscriberBase {

 /**
  * {@inheritdoc}
  */
 protected function alterRoutes(RouteCollection $collection) {
   // global $user;
    // object_log("routeTest", $collection->rebuild());
    $route_provider = \Drupal::service('router.route_provider');
    $test2 = $route_provider->getAllRoutes();
    object_log("Base TEST", $test2 );


    $base_route = $route_provider->getRouteByName('entity.field_config.node_field_edit_form');
    object_log("BAse route", $base_route);
     object_log("collection", $collection);
    if ($route = $collection->get('entity.field_config.user_field_edit_form')) {
    // object_log("collection", $collection);
       object_log("test","test");
      // $route->setDefault('_form', '\Drupal\form_overwrite\Form\NewUserLoginForm');
    }
    $test = array();
    foreach ($collection->getIterator() as $name => $route) {
      $test[] = $name;

      // $pippo = $route->getRoutesByNames(array("entity.field_config.node_field_edit_form"));
/*
      if($name == "quickedit.field_form") {
        object_log("okok", $route);
      }
      if($name == "entity.user.collection") {
      object_log("user route coll", $route);
      }
      if($name == "entity.field_storage_config.collection") {
        object_log("field test",  $route);
      }
      */
      if($name == "quickedit.field_form") {
          object_log("field test",  $route);
      }
      if($name == "entity.entity_form_mode.edit_form") {
        //object_log("field test",  $route);
      }
      // object_log("iter_".$name, $name);
      //    $this->routes[$name] = clone $route;
    }
    object_log("test", $test);

  }
}
