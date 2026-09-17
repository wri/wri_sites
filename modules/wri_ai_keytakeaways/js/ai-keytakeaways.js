(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.wriAiKeytakeaways = {
    attach: function (context, settings) {
      once('wri-ai-keytakeaways', '[name="ai_generate_key_takeaways"]', context).forEach(function (button) {
        // Make Drupal treat this button as an AJAX trigger.
        $(button).addClass('use-ajax-submit');

        button.addEventListener('click', function (e) {
          e.preventDefault();

          var $button = $(button);
          var $wrapper = $('#field-key-takeaways-add-more-wrapper');

          // Show throbber.
          $wrapper.after('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div><div class="message">' + Drupal.t('Generating key takeaways\u2026') + '</div></div>');
          $button.prop('disabled', true);

          // Submit via AJAX using Drupal's ajax system.
          var ajaxObject = Drupal.ajax({
            url: window.location.pathname,
            submit: {
              ai_generate_key_takeaways: $button.val(),
              form_id: $('[name="form_id"]').val(),
              form_build_id: $('[name="form_build_id"]').val(),
              form_token: $('[name="form_token"]').val(),
              _triggering_element_name: 'ai_generate_key_takeaways',
              _triggering_element_value: $button.val(),
            },
            wrapper: 'field-key-takeaways-add-more-wrapper',
            effect: 'fade',
          });

          ajaxObject.execute().then(function () {
            $('.ajax-progress').remove();
            $button.prop('disabled', false);
          }).catch(function () {
            $('.ajax-progress').remove();
            $button.prop('disabled', false);
          });
        });
      });
    }
  };

})(jQuery, Drupal);
