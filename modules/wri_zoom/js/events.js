/**
 * @file
 * WRI Event behaviors when zoom is enabled.
 */
(function (Drupal) {

  'use strict';

  Drupal.behaviors.wriZoomEventBacklink = {
    attach (context, settings) {
      const queryString = window.location.search;
      const urlParams = new URLSearchParams(queryString);
      if (urlParams.get('return')) {
        // find the href with class name='event_backlink'
        var backlinks = document.getElementsByClassName('event_backlink');
        // set the value of the form element to the browser language
        for (let i = 0; i < backlinks.length; i++) {
          backlinks[i].href = urlParams.get('return');
          backlinks[i].removeAttribute('hidden');
        }
      }
    }
  };

} (Drupal));
