/**
 * @file
 *
 * TS Form js
 */
export default function(context) {
  const $ = jQuery;

  // Set the destination url of the form based on the hidden URL value.
  // Also put the email in localStorage.
  $("#wri-stay-informed-footer").on("submit", function(e) {
    e.preventDefault();
    var el = $(this),
      action = el.find("input[name='url_action']"),
      email = el.find("input[name='email']");
    localStorage.setItem("wriEmail", email.val());
    window.location.href = action.val();
  });

  // If we've landed on a page with the right email field and there's
  // a localStorage value set, populate it and clear localStorage.
  if ($("#edit-email").length > 0 && localStorage.getItem("wriEmail")) {
    $("#edit-email").attr("value", localStorage.getItem("wriEmail"));
    localStorage.setItem("wriEmail", "");
  }

  var $inputs = $(
    "input[type=text], input[type=tel], input[type=email], .form-textarea"
  );
  $inputs
    .focus(function() {
      $(this)
        .parent()
        .addClass("move-label");
    })
    .on("blur", function() {
      $(this)
        .parent()
        .removeClass("move-label");
    });

  $.each($inputs, function() {
    if ($(this).val() !== "") {
      $(this)
        .parent()
        .addClass("has-value");
    }

    $(this).on("keydown keyup blur", function() {
      var label = $(this)
        .parent()
        .first();
      label.toggleClass("has-value", $(this).val() !== "");
    });
  });
}
