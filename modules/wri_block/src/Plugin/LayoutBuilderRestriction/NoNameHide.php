<?php

namespace Drupal\wri_block\Plugin\LayoutBuilderRestriction;

use Drupal\layout_builder_restrictions\Plugin\LayoutBuilderRestrictionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controls behavior of the per-view mode plugin.
 *
 * @LayoutBuilderRestriction(
 *   id = "no_name_hide",
 *   title = @Translation("Hide empty names"),
 *   description = @Translation("Hide blocks that show no admin label."),
 * )
 */
class NoNameHide extends LayoutBuilderRestrictionBase {

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Alter the block definitions to hide no-name blocks.
   */
  public function alterBlockDefinitions(array $definitions, array $context) {
    foreach ($definitions as $key => $block) {
      if (!isset($block['admin_label'])) {
        unset($definitions[$key]);
      }
    }
    return $definitions;
  }

}
