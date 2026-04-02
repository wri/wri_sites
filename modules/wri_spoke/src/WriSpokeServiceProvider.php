<?php

declare(strict_types=1);

namespace Drupal\wri_spoke;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines a service provider for the WRI Spoke module.
 *
 * @see https://www.drupal.org/node/2026959
 */
final class WriSpokeServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if ($container->hasDefinition('entity_share_client.form_helper')) {
      $definition = $container->getDefinition('entity_share_client.form_helper');
      // Set the new class for the service.
      $definition->setClass('Drupal\\wri_spoke\\Service\\HubFormHelper');
    }
  }

}
