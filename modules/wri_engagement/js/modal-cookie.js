(($, Drupal) => {
  var hasSignedUp = Cookies.get('has_signed_up');
  var webformModalMissing = true;

  var existingModalSettings = drupalSettings.simple_popup_blocks.settings;
  // Loop through the existing modal settings and find one where the trigger is .files-overlay-trigger-webform
  for (var i in existingModalSettings) {
    if (existingModalSettings[i].trigger_selector == '.webform-dialog') {
      webformModalMissing = false;
      break;
    }
  }
  // If the person has already signed up for email, go straight to the files overlay.
  if (hasSignedUp || webformModalMissing) {
    $('.files-overlay-trigger-webform').addClass('files-overlay-trigger').removeClass('webform-dialog');
  }

  $('#skip-registration').on('click', function() {
    $('.files-modal-registered').show();
    Cookies.set('has_signed_up', true);
  });
})(jQuery, Drupal);
