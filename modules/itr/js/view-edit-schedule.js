$ = jQuery;
console.log('view-edit-schedule.js');

var deptId = drupalSettings.itr.schedule.deptId;
$('#edit-field-department-target-id').val(deptId);

// override default exposed filters submit behavior
$('#views-exposed-form-schedules-page-1').submit(function(e) {
  e.preventDefault();
  e.returnValue = false;
});

// construct own url filtering
$('#edit-submit-schedules').click(function() {
  var url = drupalSettings.path.baseUrl + 'schedules/' + $('#edit-field-department-target-id').val();
  var params = '?' + 'status=' + $('#edit-status').val() + '&' + 'field_department_target_id=' + $('#edit-field-department-target-id').val();
  console.log(url+params);
  window.location.href = url+params;
});

$('#views-form-schedules-page-1-all').submit(function(e) {
  e.preventDefault();
  e.returnValue = false;

  var actionVal = $('#views-form-schedules-page-1-all #edit-action').val();
  var deptId = $('#edit-field-department-target-id').val();

  var deptInfoUrl = drupalSettings.path.baseUrl + 'itr_rest_view/dept/info/' + deptId + '?_format=json';
  var tokenUrl = drupalSettings.path.baseUrl + 'session/token';

  console.log('actionVal: ' + actionVal + ', deptId: ' + deptId);

  if(actionVal == 'node_publish_action') {
    // try to update ratified date via ajax first, then submit

    $.ajax({
      type: 'GET',
      url: tokenUrl,
      success: function(token) {
        $.ajax({
          type: 'GET',
          url: deptInfoUrl,
          success: function(deptData) {
            console.log(deptData);
          }
        });
      }
    });

    // $.ajax({
    //   type: 'GET',
    //   url: '/session/token',
    //   success: function(token) {
    //     $.ajax({
    //       type: 'PATCH',
    //       url: '/node/5277?_format=json',
    //       headers: {
    //         'Content-Type': 'application/json',
    //         'X-CSRF-Token' : token
    //       },
    //       data: JSON.stringify(theUpdateData),
    //       success: function(node) {
    //         console.log(node);
    //       }
    //     });
    //   }
    // });
  } else {
    $(this).off('submit');
    $(this).submit();
  }

});