/**
 * @file
 * WRI Zoom behaviors.
 */
(function (Drupal) {

  'use strict';

  Drupal.behaviors.wriZoomAp3cTrack = {
    attach (context, settings) {
      let trackEmail = settings.wriZoomAp3cTrack.email;
      if (typeof ap3c !== "undefined") {
        ap3c.track({"v":0,"email":trackEmail});
        console.log(trackEmail);
      }
    }
  };

} (Drupal));
