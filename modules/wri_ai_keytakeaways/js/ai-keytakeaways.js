/**
 * @file
 * Wires up the "Auto Generate (AI)" button on the article Key Takeaways
 * field.
 *
 * A submit button with '#ajax' configured normally needs no JS of its own
 * \u2014 Drupal's core Ajax behavior binds and handles it automatically. This
 * file exists because that automatic binding was not firing reliably for
 * this specific button in testing (it sits inside a field widget's own
 * wrapper, added via hook_form_alter rather than being part of the widget
 * definition itself \u2014 the exact cause wasn't tracked down further once the
 * manual approach below was confirmed to work). Manually constructing and
 * executing a Drupal.ajax() object reproduces what core's automatic
 * binding would otherwise do for a normal #ajax submit button.
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.wriAiKeytakeaways = {
    attach: function (context, settings) {
      // `once()` guards against attaching this click handler twice if
      // Drupal.behaviors.attach() runs again on the same button \u2014 which
      // happens on every AJAX rebuild of this form, since the button gets
      // re-rendered as part of the field widget's wrapper each time.
      once(
        'wri-ai-keytakeaways',
        '[name="ai_generate_key_takeaways"]',
        context
      ).forEach(function (button) {
        // Mark the button as AJAX-driven for consistency with Drupal's own
        // markup conventions, even though the actual submission below is
        // handled manually rather than through Drupal's automatic binding.
        $(button).addClass('use-ajax-submit');

        button.addEventListener('click', function (e) {
          // Prevent the browser's default full-page form submission \u2014 this
          // button must never actually save the node, only trigger the AI
          // generation submit handler via AJAX.
          e.preventDefault();

          var $button = $(button);
          var $wrapper = $('#field-key-takeaways-add-more-wrapper');

          // Manually show a throbber next to the field, matching what
          // Drupal's own #ajax 'progress' option would render if this
          // button were using the standard automatic binding.
          var message = Drupal.t('Generating key takeaways\u2026');
          $wrapper.after(
            '<div class="ajax-progress ajax-progress-throbber">'
            + '<div class="throbber">&nbsp;</div>'
            + '<div class="message">' + message + '</div>'
            + '</div>'
          );
          $button.prop('disabled', true);

          // Build and fire the AJAX request by hand. This mirrors exactly
          // what Drupal.Ajax would send for a submit button with #ajax
          // configured: the button's own name/value as the triggering
          // element, plus the form's own build id/token so Drupal can
          // reload and validate the correct FormState server-side.
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
            // Matches the '#ajax' => ['wrapper' => ...] value set
            // server-side in wri_ai_keytakeaways_form_node_form_alter();
            // both need to point at the same wrapper element for the
            // ReplaceCommand the server sends back to land in the right
            // place.
            wrapper: 'field-key-takeaways-add-more-wrapper',
            effect: 'fade',
          });

          // Whether the AJAX call itself succeeds or fails at the network
          // level (server-side errors are handled separately \u2014 see
          // wri_ai_keytakeaways_ajax_generate() in the PHP module \u2014 and
          // arrive as a successful response containing a rendered error
          // message), always clear the throbber and re-enable the button
          // so the editor isn't left with a stuck, unclickable control.
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
