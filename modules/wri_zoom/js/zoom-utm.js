/**
 * @file
 * WRI Zoom behaviors.
 */
(function (Drupal) {

  'use strict';

  Drupal.behaviors.wriZoomZoomUtm = {
    attach(context, settings) {
      // Loop through the url's query parameters.
      let url = new URL(window.location.href);
      for (const [key, value] of url.searchParams) {
        // if the key starts with 'utm_'.
        if (key.startsWith('utm_')) {
          // Find a form field with the same name.
          const field = document.querySelector(`input[name="${key}"]`);
          // Give the field the value of the query parameter.
          if (field) {
            field.value = value;
          }
        }

      }
    }
  };

} (Drupal));
