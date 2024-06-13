/**
 * @file
 * WRI Zoom behaviors.
 */
(function (Drupal, Cookies) {

  'use strict';

  Drupal.behaviors.wriZoomAp3cTrack = {
    attach (context, settings) {
      let trackEmail = Cookies.get('ap3c_track_email');
      if (trackEmail && typeof ap3c !== "undefined" && typeof ap3c.track !== "undefined") {
        ap3c.track({"v":0,"email":trackEmail});
        //console.log('ap3c tracked email' + trackEmail);
        Cookies.remove('ap3c_track_email', { path: '/', domain: '.' + window.location.hostname });
      }
    }
  };

} (Drupal, Cookies));
