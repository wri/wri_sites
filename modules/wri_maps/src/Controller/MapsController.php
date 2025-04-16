<?php

namespace Drupal\wri_maps\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Template\Attribute;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The maps controller.
 */
class MapsController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Get region map json data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response data.
   */
  public function regionMapJson(Request $request) {
    $data = [];

    $node_storage = $this->entityTypeManager()->getStorage('node');
    $nids = $request->query->all()['nids'] ?? [];
    $nodes = $node_storage->loadMultiple($nids);

    /** @var \Drupal\node\NodeInterface $node */
    foreach ($nodes as $nid => $node) {
      $data[$nid] = [
        'type' => $node->bundle(),
        'title' => $node->getTitle(),
        'url' => $node->toUrl()->toString(),
      ];

      switch ($node->bundle()) {
        case 'region':
          $popup = $this->getRegionMapPopup($node);
          $data[$nid]['popup'] = $this->renderer->render($popup);
          break;

        case 'international_office':
          /** @var \Drupal\taxonomy\TermInterface $country_term */
          $country_term = $node->field_region->entity ?? NULL;

          if ($country_term) {
            /** @var \Drupal\taxonomy\TermInterface $region_term */
            $region_term = $country_term->parent->entity ?? NULL;

            if ($region_term) {
              $region_nodes = $node_storage->loadByProperties([
                'type' => 'region',
                'field_region' => $region_term->id(),
              ]);
              if ($region_nodes) {
                /** @var \Drupal\node\NodeInterface $region_node */
                $region_node = reset($region_nodes);
                $popup = $this->getRegionMapPopup($region_node);
                $data[$nid]['popup'] = $this->renderer->render($popup);
              }
            }
          }
          break;
      }
    }

    $data['#cache'] = [
      'max-age' => $this->config('system.performance')->get('cache')['page']['max_age'],
      'contexts' => [
        'url.query_args',
      ],
    ];

    $response = new CacheableJsonResponse($data);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($data));

    return $response;
  }

  /**
   * Get region map popup content for a node.
   *
   * @param \Drupal\node\NodeInterface $region_node
   *   The region node.
   *
   * @return array
   *   The region map popup content as render array.
   */
  protected function getRegionMapPopup(NodeInterface $region_node) {
    /** @var \Drupal\taxonomy\TermInterface $region_term */
    $region_term = $region_node->field_region->entity ?? NULL;
    $term_storage = $this->entityTypeManager()->getStorage('taxonomy_term');
    $node_storage = $this->entityTypeManager()->getStorage('node');

    $attributes = new Attribute();

    if ($region_term) {
      $region_link = [
        '#type' => 'link',
        '#title' => $region_node->getTitle(),
        '#url' => $region_node->toUrl(),
      ];
      $io_links = [];

      $country_tree = $term_storage->loadTree('regions', $region_term->id());
      if ($country_tree) {
        $io_nodes = $node_storage->loadByProperties([
          'type' => 'international_office',
          'field_region' => array_column($country_tree, 'tid'),
        ]);

        /** @var \Drupal\node\NodeInterface $io_node */
        foreach ($io_nodes as $io_node) {
          $io_links[] = [
            '#type' => 'link',
            '#title' => $io_node->getTitle(),
            '#url' => $io_node->toUrl(),
          ];
        }
      }

      return [
        '#theme' => 'wri_region_map_popup',
        '#attributes' => $attributes,
        '#region_link' => $region_link,
        '#international_office_links' => $io_links,
      ];
    }

    return [];
  }

}
