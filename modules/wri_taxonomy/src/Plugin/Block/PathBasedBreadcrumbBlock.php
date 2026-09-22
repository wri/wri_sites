<?php

declare(strict_types=1);

namespace Drupal\wri_taxonomy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
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
    protected TitleResolverInterface $titleResolver,
    protected RequestStack $requestStack,
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
      $container->get('title_resolver'),
      $container->get('request_stack'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'home_link_text' => '',
      'home_link_url' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['home_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Home link text'),
      '#description' => $this->t('Replaces "Home" as the first breadcrumb. Leave blank to keep it.'),
      '#default_value' => $this->configuration['home_link_text'],
    ];
    $form['home_link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Home link URL'),
      '#description' => $this->t('An internal path such as %path. Leave blank to keep the front page.', ['%path' => '/resources']),
      '#default_value' => $this->configuration['home_link_url'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    $url = trim((string) $form_state->getValue('home_link_url'));
    if ($url !== '' && !in_array($url[0], ['/', '?', '#'], TRUE)) {
      $form_state->setErrorByName('home_link_url', $this->t('The URL must start with /, ? or #.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['home_link_text'] = trim((string) $form_state->getValue('home_link_text'));
    $this->configuration['home_link_url'] = trim((string) $form_state->getValue('home_link_url'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $breadcrumb = $this->pathBasedBreadcrumbBuilder->build($this->routeMatch);
    $links = $breadcrumb->getLinks();

    // Swap the builder's front-page link for the configured one.
    $text = $this->configuration['home_link_text'];
    $path = $this->configuration['home_link_url'];
    if ($links && ($text !== '' || $path !== '') && $links[0]->getUrl()->isRouted() && $links[0]->getUrl()->getRouteName() === '<front>') {
      $links[0] = Link::fromTextAndUrl(
        $text !== '' ? $text : $links[0]->getText(),
        $path !== '' ? Url::fromUserInput($path) : $links[0]->getUrl(),
      );
    }

    $cacheability = CacheableMetadata::createFromObject($breadcrumb)->addCacheContexts(['route']);

    // Append the current page's title, unlinked.
    $request = $this->requestStack->getCurrentRequest();
    $route = $this->routeMatch->getRouteObject();
    if ($request && $route && ($title = $this->titleResolver->getTitle($request, $route))) {
      $links[] = Link::fromTextAndUrl($title, Url::fromRoute('<none>'));
      foreach ($this->routeMatch->getParameters() as $parameter) {
        if ($parameter instanceof EntityInterface) {
          $cacheability->addCacheableDependency($parameter);
        }
      }
    }

    $build = [
      '#theme' => 'breadcrumb__wri_taxonomy_path_based',
      '#links' => $links,
    ];
    $cacheability->applyTo($build);

    return $build;
  }

}
