<?php

namespace Drupal\wri_spoke\Path;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides canonical URLs for events to point to the hub.
 */
class SpokeHostProcessor implements OutboundPathProcessorInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a path processor.
   *
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(AdminContext $admin_context, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->adminContext = $admin_context;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    $spoke_config = $this->configFactory->get('wri_spoke.settings');
    if ($spoke_config->get('ignore_hub_url')) {
      return $path;
    }
    try {
      $url = Url::fromUri('internal:' . $path);
    }
    catch (\Throwable $throwable) {
      return $path;
    }
    if (!$url) {
      return $path;
    }

    if (!$url->isRouted()) {
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
    if (isset($options['language'])) {
      try {
        $node = $node->getTranslation($options['language']->getId());
      }
      catch (\Exception $e) {
      }
    }
    if ($node->bundle() == 'event' && $node->field_hub_canonical_url->value) {
      $options['absolute'] = TRUE;
      $hub_url = parse_url($node->field_hub_canonical_url->value);
      $options['base_url'] = $hub_url['scheme'] . "://" . $hub_url['host'];
      // The prefix is used to add a translation code, but in this case we don't
      // need it added: we have our full URL coming from the hub url field.
      unset($options["prefix"]);
      // Always add returnTo, but leave it empty so JS can set it per page.
      if (!$this->adminContext->isAdminRoute()) {
        $options['query']['returnTo'] = '';
      }
      $path = $hub_url['path'];
    }
    return $path;
  }

}
