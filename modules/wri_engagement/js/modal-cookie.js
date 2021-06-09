(($, Drupal) => {
  var hasSignedUp = Cookies.get('has_signed_up');
  var webformModalMissing = true;

  var existingModalSettings = drupalSettings.simple_popup_blocks.settings;
  // Loop through the existing modal settings and find one where the trigger is .files-overlay-trigger-webform
  for (var i in existingModalSettings) {
    if (existingModalSettings[i].trigger_selector == '.files-overlay-trigger-webform') {
      webformModalMissing = false;
      break;
    }
  }
  // If the person has already signed up for email, go straight to the files overlay.
  if (hasSignedUp || webformModalMissing) {
    $('.files-overlay-trigger-webform').addClass('files-overlay-trigger').removeClass('files-overlay-trigger-webform');
  }
  // If you ask to show the files overlay from within an overlay, do it, and close the current modal.
  $('.files-overlay-trigger').click(function () {
    // Close the modal.
    $(this).parents('.spb-popup-main-wrapper').find('.spb_close').click();
  });
})(jQuery, Drupal);
