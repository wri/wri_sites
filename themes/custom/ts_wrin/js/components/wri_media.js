/**
 * @file
 *
 * WRI Media js.
 */
export default function(context) {
  const $ = jQuery;

  $(".field--name-field-media-oembed-video").on("mouseenter", function() {
    $(this).addClass("playing");
  });
}
