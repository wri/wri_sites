<?php

namespace Drupal\wri_search\Plugin\facets\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor that excludes specified items.
 *
 * @FacetsProcessor(
 *   id = "limit_to_parent",
 *   label = @Translation("Limit to parent"),
 *   description = @Translation("If this is a dependent facet, make sure only children of the chosen facet shows up."),
 *   stages = {
 *     "build" = 50
 *   }
 * )
 */
class LimitToParentProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {
  /**
   * The language manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetsManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facetStorage;

  /**
   * Constructs a new object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facets_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DefaultFacetManager $facets_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->facetsManager = $facets_manager;
    $this->facetStorage = $entity_type_manager->getStorage('facets_facet');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('facets.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $conditions = $facet->getProcessorConfigs();

    if (isset($conditions["dependent_processor"]["settings"])) {
      // Load up any selected facet values.
      foreach ($conditions["dependent_processor"]["settings"] as $facet_id => $condition_settings) {

        /** @var \Drupal\facets\Entity\Facet $current_facet */
        $current_facet = $this->facetStorage->load($facet_id);
        $current_facet = $this->facetsManager->returnProcessedFacet($current_facet);

        $active_facets = $current_facet->getActiveItems();
      }
      $child_values = [];
      foreach ($results as $option_id => $result) {
        $child_values[$result->getRawValue()] = $result->getRawValue();
      }

      $results = \Drupal::service('wri_search.pretty_facets_helper')->limitFacetsByParentId($active_facets, $child_values);
    }

    return $results;
  }

}
