<?php

namespace Drupal\wri_spoke\Path;

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
      $node = Node::load($node);
    }
    if (!($node instanceof Node)) {
      return $path;
    }
    if ($node->bundle() == 'event' && $node->field_hub_canonical_url->value) {
      $options['absolute'] = TRUE;
      $hub_url = parse_url($node->field_hub_canonical_url->value);
      $options['base_url'] = $hub_url['scheme'] . "://" . $hub_url['host'];
      $path = $hub_url['path'];
    }
    return $path;
  }

}
