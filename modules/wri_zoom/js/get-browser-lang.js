/**
 * @file
 * WRI Zoom behaviors.
 */
(function (Drupal) {

  'use strict';

  Drupal.behaviors.wriZoomGetBrowserLang = {
    attach (context, settings) {
      // find the form element with name='preferred_language'
      var language_fields = document.getElementsByClassName('set-language');
      // set the value of the form element to the browser language
      for (let i = 0; i < language_fields.length; i++) {
        language_fields[i].value = navigator.language;
      }
    }
  };

} (Drupal));
