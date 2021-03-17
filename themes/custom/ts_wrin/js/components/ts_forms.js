/**
 * @file
 *
 * TS Form js
 */
export default function(context) {
  const $ = jQuery;

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
