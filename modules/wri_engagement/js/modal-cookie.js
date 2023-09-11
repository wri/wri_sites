(($, Drupal) => {
  Drupal.behaviors.tsDownloadsCookie= {
    attach: function(context, settings) {
      var hasSignedUp = Cookies.get('has_signed_up');

      // If the person has already signed up for email, go straight to the files overlay.
      if (hasSignedUp) {
        $('.view-node-downloads').show();
        $('.downloads-submission-form').hide();
        $('.ui-dialog-buttonpane').hide();
      } else {
        $('.view-node-downloads').hide();
        $('.downloads-submission-form').show();
      }

      $('#skip-registration').on('click', function () {
        $('.view-node-downloads').show();
        $('.downloads-submission-form').hide();
        $('.ui-dialog-buttonpane').hide();
      });
    }
  };
})(jQuery, Drupal);
