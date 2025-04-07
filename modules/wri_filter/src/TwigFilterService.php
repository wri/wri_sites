<?php

namespace Drupal\wri_filter;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Service class for adding twig methods.
 */
class TwigFilterService extends AbstractExtension {
  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The WRI filter service.
   *
   * @var \Drupal\wri_filter\FilterService
   */
  protected $filterService;

  /**
   * Constructs a new GetCurrentFilter object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   RequestStack class.
   * @param \Drupal\wri_filter\FilterService $filterService
   *   Filter service class.
   */
  public function __construct(RequestStack $requestStack, FilterService $filterService) {
    $this->requestStack = $requestStack;
    $this->filterService = $filterService;
  }

  /**
   * {@inheritDoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('get_current_filter', [
        $this,
        'getCurrentFilter',
      ]),
      new TwigFunction('get_all_filters', [
        $this,
        'getAllFilters',
      ]),
    ];
  }

  /**
   * Returns the current content filter.
   */
  public function getCurrentFilter() {
    return $this->filterService->getCurrentFilter();
  }

  /**
   * Returns all possible filters.
   */
  public function getAllFilters() {
    return $this->filterService->getAllFilters();
  }

}
