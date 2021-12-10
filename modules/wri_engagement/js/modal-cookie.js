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

  // Reset when opening the popup.
  $('.files-overlay-trigger').click(function () {
    // Show the files list and hide all other forms.
    $('.files-modal-unregistered').hide();
    $('.files-modal-confirmation').hide();
    $('.files-modal-registered').show();
  });

  // Show the files list for the user to choose.
  $('.files-modal-unregistered').hide();
  $('.files-modal-registered').show();

  // Once one of the files is clicked on, keep it in memory and move on.
  let href = '';
  $('article.media a.download').on('click', function(e){
    // Save the file url to open later.
    e.preventDefault();
    href = $(this).attr('href');

    // Hide the file list.
    $('.files-modal-registered').hide();

    // Show or skip the registration form before opening the file on a new tab.
    var skipRegistration = Cookies.get('skip_registration');
    if (skipRegistration) {
      // Open the file in a new tab.
      window.open(href, '_blank');
      // Hide the registration form, display the confirmation message.
      $('.files-modal-unregistered').hide();
      $('.files-modal-confirmation').show();
    }
    else {
      // Show the registration form.
      $('.files-modal-unregistered').show();

      // Update the form to include the file selected by user.
      $('#file_path').val(href);

      // Email validation.
      function validateEmail(email) {
        let re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
      }
      // Registration submit handler.
      $('form#pardot-subscription').on('submit', function (e) {
        e.preventDefault();
        // Reset error messages.
        if ($('span.required-error').length) {
          $('span.required-error').remove();
        }
        if (!$('span.registration-failed').length) {
          $('span.registration-failed').remove();
        }
        // Validate form and try to post.
        if (validateEmail(document.getElementById('email').value) && $('input#last_name').val() !== '' && $('input#email').val() !== '') {
          window.open(href, '_blank');
          $.post('https://connect.wri.org/l/120942/2021-04-06/528nh6', $(this).serialize(), function (data) {
            console.log('registration successful');
          }).fail(function () {
            console.log('registration failed');
          });
          $('.files-modal-unregistered').hide();
          $('.files-modal-confirmation').show();
          Cookies.set('skip_registration', true);
          // Once we test posting the registration above, we can also include a message when it fails.
          //   .fail(function () {
          //   // This is executed when the call to mail.php failed.
          //   if (!$('span.registration-failed').length) {
          //     $('input.pardot-submission').after('<span class="registration-failed">Registration failed, please try again.</span>');
          //   }
          // });
        }
        else {
          $('input#last_name').css({"border":"2px solid red"});
          $('input#email').css({"border":"2px solid red"});
          if (!$('span.required-error').length) {
            $('input.pardot-submission').after('<span class="required-error">Please fill in the required fields highlighted in red.</span>');
          }
        }
      });
      // Skip registration.
      $('#skip-registration').on('click', function () {
        // Open the file and display the confirmation message.
        window.open(href, '_blank');
        $('.files-modal-confirmation').show();
        // Hide the registration form.
        $('.files-modal-unregistered').hide();
      })
    }
  });
})(jQuery, Drupal);
