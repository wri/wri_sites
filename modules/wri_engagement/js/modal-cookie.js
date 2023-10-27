(($, Drupal) => {
  Drupal.behaviors.tsDownloadsCookie= {
    attach: function(context, settings) {
      var hasSignedUp = Cookies.get('has_signed_up');

      // If the person has already signed up for email, go straight to the files overlay.
      if (hasSignedUp) {
        $('.downloads-list').show();
        $('.downloads-submission-form').hide();
        $('.ui-dialog-buttonpane').hide();
      } else {
        $('.downloads-list').hide();
        $('.downloads-submission-form').show();
      }

      $('#skip-registration').on('click', function () {
        $('.downloads-list').show();
        $('.downloads-submission-form').hide();
        $('.ui-dialog-buttonpane').hide();
      });
    }
  };
})(jQuery, Drupal);
