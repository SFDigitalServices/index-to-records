function updateTitleWithDepartmentName(select) {
  var selector = '#' + $(select).attr('id') + ' option:selected';
  $('#edit-title-0-value').val($(selector).text() + ' Department Information');
}

$(window).on('load', function() {
  updateTitleWithDepartmentName($('#edit-field-department-name'));
  $('#edit-field-department-name').change(function() {
    updateTitleWithDepartmentName(this);
  });
});

