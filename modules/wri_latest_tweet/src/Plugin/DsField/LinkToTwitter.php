<?php

namespace Drupal\wri_latest_tweet\Plugin\DsField;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders Past Event if the event is over.
 *
 * @DsField(
 *   id = "link_to_twitter",
 *   title = @Translation("Follow @link"),
 *   entity_type = "block_content",
 *   ui_limit = {"latest_tweet|*"}
 * )
 */
class LinkToTwitter extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->configuration['entity'];
    $link = [];

    if (isset($entity->field_twitter_user->value)) {
      $url = Url::fromUri('https://twitter.com/' . $entity->field_twitter_user->value);
      $text = $this->t('Follow @@name', ['@name' => $entity->field_twitter_user->value]);
      $link[] = [
        '#title' => $text,
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => ['class' => ['small button twitter']],
      ];
    }

    return $link;
  }

}
