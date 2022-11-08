<?php

namespace Drupal\wri_latest_tweet;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\twitter_api_block\TwitterManager;

/**
 * Default Twitter manager communicator.
 */
class WriTwitterManager extends TwitterManager {

  /**
   * {@inheritdoc}
   *
   * Adds the hide_media and align parameters to the oembed url.
   */
  public function renderTweet(array $tweet) {
    try {
      $id = $tweet['id'] ?? NULL;
      $username = $tweet['username'] ?? NULL;
      if (!$id || !$username) {
        throw new \Exception("Missing ID or username to render the tweet <pre>" . Json::encode($tweet) . "</pre>'");
      }

      $tweet_uri = sprintf('https://twitter.com/%s/status/%s', $tweet['username'] ?? NULL, $tweet['id'] ?? NULL);
      $tweet_url = sprintf('https://publish.twitter.com/oembed?%s', UrlHelper::buildQuery([
        'url' => $tweet_uri,
        'hide_media' => TRUE,
        'align' => 'center',
      ]));

      $response = $this->httpClient->request('GET', $tweet_url);
      return Json::decode($response->getBody()->getContents());
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }

    return [];
  }

}
