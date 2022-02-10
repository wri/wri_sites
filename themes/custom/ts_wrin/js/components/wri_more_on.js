/**
 * @file
 *
 * WRI More On js.
 */
export default function(context) {
  const $ = jQuery;

  $('.mobile.more-on .field-label a').click(function(e){
    e.preventDefault();
  })

  $('.mobile.more-on .field-label').click(function(){
    var el = $(this).parent();
    if (el.hasClass('active')) {
      el.removeClass('active');
    } else {
      el.addClass('active');
    }
  })
}
