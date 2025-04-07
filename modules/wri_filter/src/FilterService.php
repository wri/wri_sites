<?php

namespace Drupal\wri_filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines FilterService Class.
 */
class FilterService {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Path\PathMatcher definition.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * Entity type manager class.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Constructs a new FilterService object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   RequestStack Class.
   * @param \Drupal\Core\Path\PathMatcher $path_matcher
   *   Pathmatcher Class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context.
   */
  public function __construct(RequestStack $request_stack, PathMatcher $path_matcher, EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $current_route_match, AdminContext $admin_context) {
    $this->requestStack = $request_stack;
    $this->pathMatcher = $path_matcher;
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $current_route_match;
    $this->adminContext = $admin_context;
  }

  /**
   * Get the name of the filter cookie.
   */
  public function getFilterCookieName() {
    return 'STYXKEY_wri_filter';
  }

  /**
   * Get pages that are allowed to be filtered.
   *
   * @return array
   *   Array of pages that can be filtered on the site.
   */
  public function getFilterablePages() {
    return [
      '/',
      '/research',
      '/data',
      '/initiatives',
      '/insights',
    ];
  }

  /**
   * Returns the currently active content filter.
   *
   * @param bool $id_only
   *   Should this just return the id?
   *
   * @return string
   *   Term name from cookie id.
   */
  public function getCurrentFilter($id_only = FALSE) {
    $request = $this->requestStack->getCurrentRequest();
    $cookie_value = $request->cookies->get($this->getFilterCookieName());
    if ($id_only) {
      $filter_name = $cookie_value;
    }
    else {
      $filter_name = $this->getFilterNameFromId($cookie_value);
    }

    return $filter_name;
  }

  /**
   * Returns all possible filters.
   */
  public function getAllFilters() {
    $filters = [];
    $vid = 'topics_and_subtopics';
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid, $parent = 0, $max_depth = 1);
    foreach ($terms as $term) {
      $term_object = $this->entityTypeManager->getStorage('taxonomy_term')->load($term->tid);
      $exclude = FALSE;
      // Determine if this topic should be excluded.
      if ($term_object->hasField('field_topic_filter_excluded') && ($excluded_value = $term_object->get('field_topic_filter_excluded')) && !$excluded_value->isEmpty()) {
        $excluded_value_setting = $excluded_value->getValue();
        if ($excluded_value_setting[0]['value'] === '1') {
          $exclude = TRUE;
        }
      }
      if (!$exclude) {
        $filters[$term->tid] = [
          'id' => $term->tid,
          'name' => strtolower($term->name),
        ];
      }
    }
    return $filters;
  }

  /**
   * Get filter name from provided id.
   *
   * @param int $id
   *   Taxonomy term tid.
   *
   * @return string
   *   Name of the Term.
   */
  public function getFilterNameFromId($id) {
    $all_filters = $this->getAllFilters();
    if (isset($all_filters[$id])) {
      return $all_filters[$id]['name'];
    }
    return '';
  }

  /**
   * Check if the current page is filterable.
   *
   * @return bool
   *   True if the page is allowed to be filtered.
   */
  public function pageIsFilterable() {
    $return = FALSE;
    $url = Url::fromRoute('<current>');
    $route = $this->routeMatch->getRouteObject();
    $is_admin = $this->adminContext->isAdminRoute($route);
    if ($is_admin) {
      return TRUE;
    }
    $filterable_pages = $this->getFilterablePages();
    if (in_array($url->toString(), $filterable_pages)) {
      $return = TRUE;
    }
    elseif (in_array('/', $filterable_pages) && $this->pathMatcher->isFrontPage()) {
      $return = TRUE;
    }

    return $return;
  }

}
