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

  // Show or skip the registration form.
  // var skipRegistration = Cookies.get('skip_registration');
  // if (skipRegistration) {
  //   // Hide the registration form, display the files.
  //   $('.files-modal-unregistered').hide();
  // }
  // else {
  //   // Hide the files list.
  //   $('.files-modal-registered').hide();
  //   // Email validation.
  //   function validateEmail(email) {
  //     let re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  //     return re.test(email);
  //   }
  //   // Registration submit handler.
  //   $('form#pardot-subscription').on('submit', function (e) {
  //     e.preventDefault();
  //     if (validateEmail(document.getElementById('email').value)) {
  //       $.post('https://connect.wri.org/l/120942/2021-04-06/528nh6\n', $(this).serialize(), function (data) {
  //         $('.files-modal-unregistered').hide();
  //         $('.files-modal-registered').show();
  //         Cookies.set('skip_registration', true);
  //       }).fail(function() {
  //         // This is executed when the call to mail.php failed.
  //         console.log('registration failed');
  //         $('.files-modal-unregistered').hide();
  //         $('.files-modal-registered').show();
  //         Cookies.set('skip_registration', true);
  //       });
  //     }
  //     else {
  //       $('#email').css({"border":"2px solid red"});
  //     }
  //   });
  //   // Skip registration.
  //   $('#skip-registration').on('click', function() {
  //     $('.files-modal-unregistered').hide();
  //     $('.files-modal-registered').show();
  //     Cookies.set('skip_registration', true);
  //   })
  // }
})(jQuery, Drupal);
