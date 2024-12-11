/**
 * @file
 *
 * WRI Datetime js.
 */
export default function(context) {
  const $ = jQuery;

  $('span.gmt').once().each(function(e) {
    var gmt_text = $(this).text().trim();
      if (!gmt_text) {
        $(this).addClass('hidden');
      } else if (gmt_text.startsWith('-') || gmt_text.startsWith('+')) {
        $(this).prepend('GMT');
      }
  });
}
