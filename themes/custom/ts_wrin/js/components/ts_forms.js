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
    "input[type=date], input[type=datetime-local], input[type=text], input[type=email], input[type=month], " +
      "input[type=number], input[type=search], input[type=tel], input[type=text], input[type=time], " +
      "input[type=url], input[type=week], input[type=password], .form-textarea"
  );
  $inputs
    .focus(function() {
      if (
        !$(this)
          .closest("form")
          .hasClass("webform-submission-form")
      ) {
        $(this)
          .parents(".js-form-item")
          .first()
          .addClass("move-label");
      }
    })
    .on("blur", function() {
      if (
        !$(this)
          .closest("form")
          .hasClass("webform-submission-form")
      ) {
        $(this)
          .parents(".js-form-item")
          .first()
          .removeClass("move-label");
      }
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
