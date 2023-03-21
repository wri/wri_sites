<?php

namespace Drupal\wri_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'tokenized_link_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "tokenized_link_field_formatter",
 *   label = @Translation("WRI Resource link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class TokenizedLinkFieldFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  protected function buildUrl(LinkItemInterface $item) {
    $entity = $item->getEntity();
    $untokenized_url = $item->uri;
    $item_url = \Drupal::token()->replace($untokenized_url, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
    $url = Url::fromUri($item_url);

    if (empty(\Drupal::hasService('wri_search.pretty_facets_helper')) || !$url->isRouted()) {
      return $url;
    }

    // Break up item_url into the set of facets, and pass to getPrettyPaths().
    $query_items = $url->getOptions()['query'] ?? [];

    // Full query looks like: /resources/topic/governance-227/type/65/subtype/15401/region/8934/country/8898.
    foreach ($query_items as $key => $value) {
      if ($value) {
        $filters_current_result[$key][] = $value;
      }
    }

    $item_uri = 'internal:/' . $url->getInternalPath();
    if (!empty($filters_current_result)) {
      $item_uri .= \Drupal::service('wri_search.pretty_facets_helper')->getPrettyPaths($filters_current_result);
    }
    $value = $item->getValue();
    $value['uri'] = $item_uri;
    $item->setValue($value);
    $url = parent::buildUrl($item);

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    foreach ($items as $delta => $item) {
      $items[$delta]->_attributes += ['class' => 'button small'];
    }
    $element = parent::viewElements($items, $langcode);
    return $element;
  }

}
