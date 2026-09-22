<?php

declare(strict_types=1);

namespace Drupal\wri_taxonomy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a breadcrumb block built from the URL path, not entity hierarchy.
 *
 * Unlike the core "Breadcrumbs" block, which goes through
 * \Drupal\Core\Breadcrumb\BreadcrumbManager and lets the first applicable
 * breadcrumb_builder win (e.g. \Drupal\taxonomy\TermBreadcrumbBuilder), this
 * block calls \Drupal\system\PathBasedBreadcrumbBuilder directly, so the
 * trail always reflects the current URL alias's path segments rather than a
 * term's (possibly empty or stale) parent hierarchy.
 *
 * @Block(
 *   id = "wri_taxonomy_path_based_breadcrumb",
 *   admin_label = @Translation("Path-based Breadcrumbs"),
 *   category = @Translation("WRI block"),
 * )
 */
final class PathBasedBreadcrumbBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a PathBasedBreadcrumbBlock object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected PathBasedBreadcrumbBuilder $pathBasedBreadcrumbBuilder,
    protected RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('system.breadcrumb.default'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $breadcrumb = $this->pathBasedBreadcrumbBuilder->build($this->routeMatch);

    $build = [
      '#theme' => 'breadcrumb',
      '#links' => $breadcrumb->getLinks(),
    ];
    CacheableMetadata::createFromObject($breadcrumb)->applyTo($build);

    return $build;
  }

}
