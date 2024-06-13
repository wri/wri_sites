/**
 * @file
 * WRI Zoom behaviors.
 */
(function (Drupal, ap3c) {

  'use strict';

  Drupal.behaviors.wriZoomAp3cTrack = {
    attach (context, settings) {
      ap3c.track({"v":0,settings.wriZoomAp3cTrack.email});
    }
  };

} (Drupal, ap3c));
