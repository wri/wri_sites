(($, Drupal) => {
  var hasSignedUp = Cookies.get('has_signed_up');
  var webformModalMissing = true;

  var existingModalSettings = drupalSettings.simple_popup_blocks.settings;
  // Loop through the existing modal settings and find one where the trigger is .files-overlay-trigger-webform.
  for (var i in existingModalSettings) {
    if (existingModalSettings[i].trigger_selector == '.files-overlay-trigger-webform') {
      webformModalMissing = false;
      break;
    }
  }
  // If the person has already signed up for email, go straight to the files overlay (obsolete?).
  if (hasSignedUp || webformModalMissing) {
    $('.files-overlay-trigger-webform').addClass('files-overlay-trigger').removeClass('files-overlay-trigger-webform');
  }
  // If you ask to show the files overlay from within an overlay, do it, and close the current modal.
  $('.files-overlay-trigger').click(function () {
    // Close the modal.
    $(this).parents('.spb-popup-main-wrapper').find('.spb_close').click();
  });
  // Display the files list but prevent direct download. Record the selected file.
  $('#files-registration-form').hide();
  let downloads = {};
  $('#files-modal-list a').on('click', function (e) {
    e.preventDefault();
    // Get selected file url and set it in the registration form.
    downloads.fileUrl = $(this).attr('href');
    $('form#pardot-subscription input#file_path').prop('value', downloads.fileUrl);
    // Check if skip cookie is set.
    downloads.skipRegistration = Cookies.get('skip_registration');
    if (downloads.skipRegistration) {
      // Close the modal.
      $(this).parents('.spb-popup-main-wrapper').find('.spb_close').click();
      // Open file in new tab.
      window.open(downloads.fileUrl);
      // Get back to initial state where the download list is shown.
      $('#files-registration-form').hide();
      $('#files-modal-list').show();
    }
    else {
      // Otherwise display the form after clicking the file.
      $('#files-modal-list').hide();
      $('#files-registration-form').show();
      // Email validation.
      function validateEmail(email) {
        let re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
      }
      // Form registration submit handler.
      $('form#pardot-subscription').on('submit', function (e) {
        e.preventDefault();
        if (validateEmail(document.getElementById('email').value)) {
          $.post('https://connect.wri.org/l/120942/2021-04-06/528nh6\n', $(this).serialize());
          // Registration will return false negatives due to CORS.
          Cookies.set('skip_registration', true);
          // Trigger the confirmation snippet after form submitted.
          /* Twitter universal website tag code */
          !function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments);
          },s.version='1.1',s.queue=[],u=t.createElement(n),u.async=!0,u.src='//static.ads-twitter.com/uwt.js',
              a=t.getElementsByTagName(n)[0],a.parentNode.insertBefore(u,a))}(window,document,'script');
          // Insert Twitter Pixel ID and Standard Event data below
          twq('init','o2tss');
          twq('track','PageView');
          /* End Twitter universal website tag code */
          // Open file in new tab.
          window.open(downloads.fileUrl);
          // Close the modal.
          $(this).parents('.spb-popup-main-wrapper').find('.spb_close').click();
          // Get back to initial state where the download list is shown.
          $('#files-registration-form').hide();
          $('#files-modal-list').show();
        }
        else {
          $('#email').css({"border": "2px solid red"});
        }
      });
      // Skip registration.
      $('#skip-registration').on('click', function () {
        // Close the modal.
        $(this).parents('.spb-popup-main-wrapper').find('.spb_close').click();
        // Open file in new tab.
        window.open(downloads.fileUrl);
        // Get back to initial state where the download list is shown.
        $('#files-registration-form').hide();
        $('#files-modal-list').show();
        Cookies.set('skip_registration', true);
      })
    }
  });
})(jQuery, Drupal);
