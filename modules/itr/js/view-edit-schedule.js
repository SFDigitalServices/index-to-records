

window.onload = function() {
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

  $('form[data-drupal-selector*="views-form-schedules"]').submit(function(e) {
    var _submit = $(this)[0].submit;
    var _this = $(this)[0];
    e.preventDefault();
    e.returnValue = false;

    var actionVal = $('form[data-drupal-selector*="views-form-schedules"] #edit-action').val();
    var deptId = $('#edit-field-department-target-id').val();

    var deptInfoUrl = drupalSettings.path.baseUrl + 'itr_rest_view/dept/info/' + deptId + '?_format=json';
    var tokenUrl = drupalSettings.path.baseUrl + 'session/token';
    var hasCheckedRecords = $('form[data-drupal-selector*="views-form-schedules"] input[type="checkbox"]:checked').length > 0 ? true : false;
    console.log('actionVal: ' + actionVal + ', deptId: ' + deptId + ', hasCheckedRecords: ' + hasCheckedRecords);

    if(actionVal == 'node_publish_action' && hasCheckedRecords) {
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
              if(deptData.length > 0) {
                var deptInfoNid = deptData[0].nid[0].value;
                console.log(deptInfoNid);
                var today = new Date();
                var month = today.getMonth() + 1;
                var date = today.getDate();
                var year = today.getFullYear();
                var todayStr = month + '/' + date + '/' + year + ' ' + today.toLocaleTimeString();
                var theUpdateData = {
                  type: [{ target_id: 'department_information' }],
                  field_schedule_ratified_date: [{ value: todayStr }]
                };
                $.ajax({
                  type: 'PATCH',
                  url: '/node/' + deptInfoNid + '?_format=json',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token' : token
                  },
                  data: JSON.stringify(theUpdateData),
                  success: function(node) {
                    console.log('ratified date updated');
                  },
                  complete: function() {
                    $(_this).off('submit');
                    $(_this).submit();
                    _submit.call(_this);
                  }
                });
              } else {
                console.log('dept info data does not exist, prompt user to enter dept info before publishing');
              }
            }
          });
        }
      });


    } else {
      $(this).off('submit');
      $(this).submit();
    }

  });
};