$ = jQuery;
console.log(Drupal.AjaxCommands);
console.log('add-record');
console.log(drupalSettings.itr.addRecord.foo);
// var path = drupalSettings.itr.path;

// $('#edit-field-department').on('change', function() {
//   var deptId = $(this).val();
//   $.ajax({
//     url: path + 'itr_rest/department_category/' + deptId,
//     data: {
//       '_format': 'json'
//     }
//   }).done(function(data) {
//     console.log(data);
//     var options = data;
//     var optionsHtml = '<option value="_none">- None - </option>';
//     for(var i=0; i<options.length; i++) {
//       optionsHtml += '<option value="' + options[i].id + '">' + options[i].name + '</option>';
//     }
//     $('#edit-field-category').html(optionsHtml);
//   });
// });

$('#node-record-form #edit-field-category, #node-record-form #edit-field-division').html('<option value="_none"> - Select Department First - </option>');

// drupal ajax adds a container div to html command
$('#edit-field-department').on('change', function() {
  Drupal.AjaxCommands.prototype.demoTestJsCommand = function(ajax, response) {
    $('#edit-field-category').html($('#edit-field-category > div').contents());
    $('#edit-field-division').html($('#edit-field-division > div').contents());
  };
});