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
      $terms = [];
      // Load up any selected facet values.
      foreach ($conditions["dependent_processor"]["settings"] as $facet_id => $condition_settings) {

        /** @var \Drupal\facets\Entity\Facet $current_facet */
        $current_facet = $this->facetStorage->load($facet_id);
        $current_facet = $this->facetsManager->returnProcessedFacet($current_facet);

        $active = $current_facet->getActiveItems();
        foreach ($active as $value) {
          // Load up children of that facet value.
          $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($value);
          $terms = array_merge(array_keys($tree), $terms);
        }
      }

      // Exclude elements not in that list.
      $good_results = [];

      /** @var \Drupal\facets\Result\ResultInterface $result */
      foreach ($results as $result) {
        $value = $result->getRawValue();

        // Compare the results to the list of valid children.
        if (in_array($value, $terms)) {
          $good_results[] = $result;
        }
      }

      $results = $good_results;
    }

    return $results;
  }

}
