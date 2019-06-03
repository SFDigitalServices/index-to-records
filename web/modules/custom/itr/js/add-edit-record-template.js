(function($) {
  $('#edit-field-template-title-0-value').on('keyup', function() {
    var val = $(this).val().length <= 255 ? $(this).val() : $(this).val().substring(0, 255);
    $('#edit-title-0-value').val(val);
  });
})(jQuery);