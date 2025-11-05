<?php

namespace Drupal\wri_performance\Plugin\Purge\Purger;

use Drupal\cloudflarepurger\Plugin\Purge\Purger\CloudFlarePurger;

// cspell:ignore Depencies
/**
 * CloudFlare purger.
 *
 * @PurgePurger(
 *   id = "cloudflare",
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
    if (TRUE || (defined('PANTHEON_ENVIRONMENT') && $_ENV['PANTHEON_ENVIRONMENT'] == 'live')) {
      return parent::invalidate($invalidations);
    }
    else {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
    }
  }

}
