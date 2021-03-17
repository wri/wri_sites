<?php

namespace Drupal\wri_search;

use Drupal\facets\Entity\Facet;
use Drupal\facets_pretty_paths\Coder\CoderPluginManager;

/**
 * Provides the pretty facets for a set of terms.
 */
class PrettyFacetsHelper {
  /**
   * The Coder plugin manager.
   *
   * @var \Drupal\facets_pretty_paths\Coder\CoderPluginManager
   */
  protected $coderManager;

  /**
   * Constructs an instance of PrettyPathsActiveFilters.
   *
   * @param \Drupal\facets_pretty_paths\Coder\CoderPluginManager $coderManager
   *   The Coder plugin manager.
   */
  public function __construct(CoderPluginManager $coderManager) {
    $this->coderManager = $coderManager;
  }

  /**
   * Gets the Search pretty path for an array of facet values.
   *
   * @param array $filters_current_result
   *   The facet values.
   *
   * @return string
   *   The pretty facet path url.
   */
  public function getPrettyPaths(array $filters_current_result) {
    $coder_plugin_manager = $this->coderManager;
    $pretty_paths_presort_data = [];
    foreach ($filters_current_result as $facet_id => $active_values) {
      foreach ($active_values as $active_value) {
        // Ensure we only load every facet and coder once.
        if (!isset($initialized_facets[$facet_id])) {
          $facet = Facet::load($facet_id);
          if (!$facet) {
            continue;
          }
          $initialized_facets[$facet_id] = $facet;
          $coder_id = $facet->getThirdPartySetting('facets_pretty_paths', 'coder', 'default_coder');
          $coder = $coder_plugin_manager->createInstance($coder_id, ['facet' => $facet]);
          $initialized_coders[$facet_id] = $coder;
        }
        $encoded_value = $initialized_coders[$facet_id]->encode($active_value);
        $pretty_paths_presort_data[] = [
          'weight' => $initialized_facets[$facet_id]->getWeight(),
          'name' => $initialized_facets[$facet_id]->getName(),
          'pretty_path_alias' => "/" . $initialized_facets[$facet_id]->getUrlAlias() . "/" . $encoded_value,
        ];
      }
    }
    $pretty_paths_presort_data = $this->sortByWeightAndName($pretty_paths_presort_data);
    $pretty_paths_string = implode('', array_column($pretty_paths_presort_data, 'pretty_path_alias'));
    return $pretty_paths_string;
  }

  /**
   * Sorts an array with weight and name values.
   *
   * It sorts first by weight, then by the alias of the facet item value.
   *
   * @param array $pretty_paths
   *   The values to sort.
   *
   * @return array
   *   The sorted values.
   */
  public function sortByWeightAndName(array $pretty_paths) {
    array_multisort(array_column($pretty_paths, 'weight'), SORT_ASC,
      array_column($pretty_paths, 'name'), SORT_ASC,
      array_column($pretty_paths, 'pretty_path_alias'), SORT_ASC, $pretty_paths);

    return $pretty_paths;
  }

}
