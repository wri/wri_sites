<?php

namespace Drupal\wri_performance\Plugin\Purge\Purger;

use Drupal\cloudflarepurger\Plugin\Purge\Purger\CloudFlarePurger;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;

/**
 * CloudFlare purger.
 *
 * @PurgePurger(
 *   id = "cloudflare_live_only",
 *   label = @Translation("CloudFlare (live only)"),
 *   description = @Translation("Live environment Purger for CloudFlare."),
 *   types = {"tag", "url", "everything"},
 *   multi_instance = FALSE,
 * )
 */
class EnvCloudFlarePurger extends CloudFlarePurger {

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    if (defined('PANTHEON_ENVIRONMENT') && $_ENV['PANTHEON_ENVIRONMENT'] == 'live') {
      return parent::invalidate($invalidations);
    }
    else {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
    }
  }

}
