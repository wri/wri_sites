<?php

namespace Drupal\wri_external_pub\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "wri_external_pub_block",
 *   admin_label = @Translation("WRI External Publications"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class ExternalPubBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Node from the context_definitions.
    $node = $this->getContextValue('node');
    $author_publications_array = [];

    if ($node instanceof NodeInterface
      && $node->bundle() === 'person') {

      $pubListSrc = "https://files.wri.org/external-publications/externalpubs.json";
      $pubList = file_get_contents($pubListSrc);
      $pubListArray = json_decode($pubList, TRUE);

      $authLast = $node->field_last_name->value;
      $authFirst = $node->field_first_name->value;
      $authAltLastArray = $node->field_alt_last_names->getValue();
      $authAltFirstArray = $node->field_alt_first_names->getValue();

      $name_fixes = [
        "á" => "a",
        "é" => "e",
        "í" => "i",
        "ó" => "o",
        "ú" => "u",
      ];

      // Create an array of all possible name combinations.
      // Note: not checking $authFirst/$authLast in case of single-name people.
      $name_variants = [];
      $name_variants[] = strtr($authFirst . ' ' . $authLast, $name_fixes);

      // Handle alternate last names.
      if (!empty($authAltLastArray)) {
        foreach ($authAltLastArray as $altLastName) {
          $name_variants[] = strtr($authFirst . ' ' . $altLastName['value'], $name_fixes);
        }
      }

      // Handle alternate first names.
      if (!empty($authAltFirstArray)) {
        foreach ($authAltFirstArray as $altFirstName) {
          $name_variants[] = strtr($altFirstName['value'] . ' ' . $authLast, $name_fixes);
        }
      }

      // Handle combinations of alternate first and last names.
      if (!empty($authAltFirstArray) && !empty($authAltLastArray)) {
        foreach ($authAltFirstArray as $altFirstName) {
          foreach ($authAltLastArray as $altLastName) {
            $name_variants[] = strtr($altFirstName['value'] . ' ' . $altLastName['value'], $name_fixes);
          }
        }
      }

      foreach ($pubListArray as $publication_entry) {
        $publicationIsFromAuthor = FALSE;
        if (isset($publication_entry['author'])) {
          $authors = [];
          foreach ($publication_entry['author'] as $author) {
            $first_name = (isset($author['given'])) ? $author['given'] : '';
            $last_name = (isset($author['family'])) ? $author['family'] : '';
            $authors[] = $last_name . ' ' . $first_name;
            $feedName = strtr($first_name . ' ' . $last_name, $name_fixes);

            // Check against all name variants.
            if (in_array($feedName, $name_variants)) {
              $publicationIsFromAuthor = TRUE;
            }
          }
        }
        if ($publicationIsFromAuthor) {
          if (isset($publication_entry['issued']['date-parts'])) {
            $publication_date = $publication_entry['issued']['date-parts'][0][0] . (isset($publication_entry['issued']['date-parts'][0][1]) ? "-" . $publication_entry['issued']['date-parts'][0][1] : "");
            $publication_date = new DrupalDateTime($publication_date);
            $date = $publication_date->getTimestamp();
          }
          $author_publications_array[] = [
            'title' => $publication_entry['title'] ?? '',
            'authors' => implode(', ', $authors),
            'container_title' => $publication_entry['container-title'] ?? '',
            'volume' => $publication_entry['volume'] ?? '',
            'issued' => $publication_entry['issued']['date-parts'][0][0] ?? '',
            'page' => $publication_entry['page'] ?? '',
            'doi' => $publication_entry['DOI'] ?? '',
            'doi_url' => 'https://doi.org/',
            'date' => $date ?? '',
          ];
        }
      }

      if (!empty($author_publications_array)) {
        usort($author_publications_array, function ($publication1, $publication2) {
          return $publication2['date'] <=> $publication1['date'];
        });
      }
      else {
        // Not publications? we just want hide the block, or empty block.
        return [];
      }

      return [
        '#publications' => $author_publications_array,
        '#author_name' => $authFirst . ' ' . $authLast,
        '#theme' => 'wri_external_pub_person_block',
        '#attached' => [
          'library' => [
            'wri_external_pub/wri_external_pub_styles',
          ],
        ],
      ];
    }

    // Empty in case the node is not a Person.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

}
