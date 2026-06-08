<?php

namespace Drupal\wri_search\Plugin\facets\processor;

use Drupal\Core\Cache\UnchangingCacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor that makes a facet depend on the state of another facet.
 *
 * @FacetsProcessor(
 *   id = "dependent_processor",
 *   label = @Translation("Dependent facet"),
 *   description = @Translation("Display this facet depending on the state of another facet."),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class DependentFacetProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  use UnchangingCacheableDependencyTrait;

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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $current_facet) {
    $build = [];
    $config = $this->getConfiguration();

    // TODO: Dynamically get all the other facet filters here instead of this clumsy textfield approach,
    // so conditions keep working.
    $build['query_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Depends on'),
      '#description' => $this->t('The "Filter identifier" value of the facet that this facet depends on.'),
      '#required' => TRUE,
      '#default_value' => $config['query_key'] ?? '',
    ];

    return parent::buildConfigurationForm($form, $form_state, $current_facet) + $build;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $conditions = $this->getConfiguration();

    if (isset($facet->allFacetValues[$conditions['query_key']])) {
      return $results;
    }

    return [];
  }

}
