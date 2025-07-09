/**
 * @file
 * WRI Event behaviors when zoom is enabled.
 */
(function (Drupal) {

  'use strict';

  Drupal.behaviors.wriEventBacklink = {
    attach (context, settings) {
      const queryString = window.location.search;
      const urlParams = new URLSearchParams(queryString);
      if (urlParams.get('returnTo')) {
        // find the href with class name='event_backlink'
        var backlinks = document.getElementsByClassName('event_backlink');
        // set the value of the form element to the browser language
        for (let i = 0; i < backlinks.length; i++) {
          backlinks[i].removeAttribute('hidden');
          backlinks[i].getElementsByTagName('a')[0].href = urlParams.get('returnTo');
        }
      }
    }
  };

} (Drupal));
