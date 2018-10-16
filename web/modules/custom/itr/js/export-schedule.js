$ = jQuery;
console.log('export-schedule');
var deptId = null;
if(drupalSettings.path.currentQuery) {
  if(drupalSettings.path.currentQuery.field_department_target_id) {
    deptId = drupalSettings.path.currentQuery.field_department_target_id;
  }
} else {
  deptId = drupalSettings.itr.exportSchedule.id;
}
if(!isNaN(deptId)) {
  console.log('deptId: ' + deptId);
  $('#edit-field-department-target-id').val(deptId);
  var deptInfoUrl = drupalSettings.path.baseUrl + 'dept/info/' + deptId + '?_format=json';
  // get the department information
  $.ajax({
    url: deptInfoUrl,
    success: function(deptInfo) {
      if(deptInfo.length > 0) {
        var dept = deptInfo[0];
        var deptInfoHtml = '';
        deptInfoHtml += '<table id="dept-info">';
        deptInfoHtml += '  <tr>';
        deptInfoHtml += '   <td><strong>Department Name</strong>:' + $('#edit-field-department-target-id option:selected').text() + '</td>';
        deptInfoHtml += '   <td><strong>Contact Email</strong>:' + dept.field_department_contact_email[0].value + '</td>';
        deptInfoHtml += '   <td><strong>Department Website</strong>:' + dept.field_department_website[0].value + '</td>';
        deptInfoHtml += '  </tr>';
        deptInfoHtml += '  <tr>';
        deptInfoHtml += '   <td><strong>Department Contact</strong>:' + dept.field_department_contact_name[0].value + '</td>';
        deptInfoHtml += '   <td><strong>Contact Phone Number</strong>:' + dept.field_department_contact_phone_n[0].value + '</td>';
        deptInfoHtml += '   <td></td>';
        deptInfoHtml += '  </tr>';
        deptInfoHtml += '</table>';

        $('#views-exposed-form-schedules-page-2').after($('#schedule-export-dept-info'));
        $('#schedule-export-dept-info').html(deptInfoHtml);
      }
    }
  })
} else {
  console.log('do nothing');
}