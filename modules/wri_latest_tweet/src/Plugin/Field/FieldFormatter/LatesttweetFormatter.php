<?php

namespace Drupal\wri_latest_tweet\Plugin\Field\FieldFormatter;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class LatesttweetFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, BlockManagerInterface $block_manager, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->blockManager = $block_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.block'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $block_manager = $this->blockManager;
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
        ],
      ];
      $plugin_block = $block_manager->createInstance('twitter_api_block_search', $config);
      // Some blocks might implement access check.
      $access_result = $plugin_block->access($this->currentUser);
      // Return empty render array if user doesn't have access.
      // $access_result can be boolean or an AccessResult class.
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
