<?php

namespace Drupal\wri_spoke\Path;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides canonical URLs for events to point to the hub.
 */
class SpokeHostProcessor implements OutboundPathProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a path processor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    try {
      $url = Url::fromUri('internal:' . $path);
    }
    catch (\Throwable $throwable) {
      return $path;
    }
    if (!$url) {
      return $path;
    }

    $routeName = $url->getRouteName();
    if ($routeName != 'entity.node.canonical') {
      return $path;
    }

    $params = $url->getRouteParameters();
    if (!isset($params['node'])) {
      return $path;
    }

    $node = $params['node'];
    if (!($node instanceof Node) && is_numeric($node)) {
      $node = $this->entityTypeManager->getStorage('node')->load($node);
    }
    if (!($node instanceof Node)) {
      return $path;
    }
    if ($node->bundle() == 'event' && $node->field_hub_canonical_url->value) {
      $options['absolute'] = TRUE;
      $hub_url = parse_url($node->field_hub_canonical_url->value);
      $options['base_url'] = $hub_url['scheme'] . "://" . $hub_url['host'];
      if ($request) {
        $options['query']['returnTo'] = $request->getScheme() . "://" . $request->getHttpHost() . $request->getRequestUri();
      }
      $path = $hub_url['path'];
    }
    return $path;
  }

}
