/**
 * @file
 *
 * WRI More On js.
 */
export default function(context) {
  const $ = jQuery;

  $(".more-on .field-label a", context).click(function(e) {
    e.preventDefault();
  });

  $(".more-on .field-label", context).click(function() {
    var el = $(this).parent();
    if (el.hasClass("active")) {
      el.removeClass("active");
    } else {
      el.addClass("active");
    }
  });
}
