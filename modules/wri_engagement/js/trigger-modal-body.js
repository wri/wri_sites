(($, Drupal) => {
  Drupal.behaviors.tsModalZIndex = {
    attach: function(context, settings) {
      // On open modal, set nav menu's z-index to lower.
      $('.files-overlay-trigger').click(function () {
        $('.publication__toc').css('z-index','1');
      });
      // On close set the z-index back to its original
      $('.files-modal-modal-close').on('click', function(){
        $('.publication__toc').css('z-index','51');
      });

      var hasSignedUp = Cookies.get('has_signed_up');
      if (!hasSignedUp) {
        $('.ui-dialog-buttonpane').addClass('hide-me');
      }
    }
  };
})(jQuery, Drupal);
