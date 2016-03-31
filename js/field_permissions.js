(function ($) {
Drupal.behaviors.fieldPermissions = {
  attach: function (context, settings) {
    var PemTable = $(context).find("#permissions");
    var PermDefaultType = $(context).find('#edit-type input:checked');
    var PermInputType = $(context).find('#edit-type input');
    /*init*/
    if (PermDefaultType.val() != 2) { PemTable.hide(); }
    /*change*/
    PermInputType.on("change",function() {
      var typeVal = $(this).val();
      if(typeVal == 0 || typeVal == 1) { PemTable.hide(); }
      else { PemTable.show();}
    });
  }
};
})(jQuery);
