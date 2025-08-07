<?php

declare(strict_types=1);

namespace Drupal\wri_toc\Plugin\Block;

use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\wri_toc\Traits\TocConfigTrait;

/**
 * Provides a horizontal bar menu block.
 *
 * @Block(
 *   id = "wri_toc_horizontal_bar_menu",
 *   admin_label = @Translation("Horizontal bar menu"),
 *   category = @Translation("Custom"),
 * )
 */
final class HorizontalBarMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $linkManager;

  use TocConfigTrait;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, MenuLinkManagerInterface $link_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->linkManager = $link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return static::getDefaultTocConfig();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    return $this->buildTocForm($form, $form_state, $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['menu'] = $form_state->getValue('menu');
    $this->configuration['color_class'] = $form_state->getValue('color_class');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $menu = $this->configuration['menu'] ?? 'page-hierarchies';
    $color_class = $this->configuration['color_class'] ?? 'black-bar';
    $menu_link = '';
    $node_title = '';

    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
      $node_title = $node->getTitle();
      $node_id = $node->id();
      $links = $this->linkManager->loadLinksByRoute('entity.node.canonical', ['node' => $node_id], $menu);
      if (!empty($links)) {
        $menu_link = current($links);
        $parent = $menu_link->getParent();
        if ($parent) {
          $menu_link_id = $parent;
        }
        else {
          $menu_link_id = $menu_link->getPluginId();
        }
      }
    }

    return [
      '#theme' => 'table_of_contents',
      '#menu_title' => $menu,
      '#value' => 'menu',
      '#menu_link' => $menu_link_id,
      '#node_title' => $node_title,
      '#color_bar' => $color_class,
    ];
  }

}
