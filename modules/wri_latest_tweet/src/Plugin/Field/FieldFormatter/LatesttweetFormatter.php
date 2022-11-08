<?php

namespace Drupal\wri_latest_tweet\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'LatestTweet' formatter.
 *
 * @FieldFormatter(
 *   id = "wri_latest_tweet_latesttweet",
 *   label = @Translation("LatestTweet"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class LatesttweetFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $block_manager = \Drupal::service('plugin.manager.block');
      // You can hard code configuration or you load from settings.
      $config = [
        'id' => 'twitter_api_block_search',
        'label_display' => 'hidden',
        'provider' => 'twitter_api_block',
        'application' => 'wri_latest_tweet',
        'options' => [
          'display' => 'embed',
          'count' => 1,
          'search' => 'from:' . $item->value,
          'tweet_fields' => '',
          'sort_order' => 'recency',
        ],
        'cache' => [
          'max_age' => 3600,
        ]
      ];
      $plugin_block = $block_manager->createInstance('twitter_api_block_search', $config);
      // Some blocks might implement access check.
      $access_result = $plugin_block->access(\Drupal::currentUser());
      // Return empty render array if user doesn't have access.
      // $access_result can be boolean or an AccessResult class
      if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
        // You might need to add some cache tags/contexts.
        $element[$delta] = [];
      }
      else {
        $element[$delta] = $plugin_block->build();
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only to this for twitter timeline strings.
    return ($field_definition->getName() == 'field_twitter_user');
  }
}
