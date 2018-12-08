

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

  // find and hide checkboxes for records that cannot be edited/deleted
  var noneditableNodes = $('.schedule-row .dropbutton-single');
  $(noneditableNodes).each(function() {
    var parentRow = $(this).parents('.schedule-row');
    var checkbox = $(parentRow).find('.form-checkbox');
    $(parentRow).addClass('itr-no-select');
    $(checkbox).attr('disabled', true);
    $(checkbox).addClass('hide');
  });

  // add click listener to select all checkbox
  $('.select-all.views-field-node-bulk-form input[type="checkbox"]').click(function() {
    setTimeout(function() {
      $('.itr-no-select').removeClass('selected');
    }, 100);
    
  });

  function publishButtonClick() {
    $('.select-all.views-field-node-bulk-form input[type="checkbox"]').click(); // select all records
    $('#edit-action').val('node_publish_action'); // select publish action
    $('#edit-submit--2').click(); // publish!
  }

  if(drupalSettings.itr.user.admin) {
    var publishLink = $('<a class="itr-publish-link" href="javascript:void(0)">Publish Schedule</a>');
    $('.view-header').append(publishLink);
    $(publishLink).click(function() {
      publishButtonClick();
    })
  }

  $('form[data-drupal-selector*="views-form-schedules"]').submit(function(e) {
    var _submit = $(this)[0].submit;
    var _this = $(this)[0];
    e.preventDefault();
    e.returnValue = false;

    var actionVal = $('form[data-drupal-selector*="views-form-schedules"] #edit-action').val();
    var deptId = $('#edit-field-department-target-id').val();

    var deptInfoUrl = drupalSettings.path.baseUrl + 'itr_rest_view/dept/info/' + deptId + '?_format=json';
    var tokenUrl = drupalSettings.path.baseUrl + 'session/token';
    var nodePatchUrl = drupalSettings.path.baseUrl + 'node';
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
                  url: nodePatchUrl + '/' + deptInfoNid + '?_format=json',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token' : token
                  },
                  data: JSON.stringify(theUpdateData),
                  success: function(node) {
                    console.log('ratified date updated');
                  },
                  error: function(resp) {
                    console.log('error');
                    console.log(resp);
                  },
                  complete: function() {
                    $(_this).off('submit');
                    $(_this).submit();
                    _submit.call(_this);
                  }
                });
              } else {
                var deptName = $('#edit-field-department-target-id option:selected').text();
                var errorMsg = 'Department information was not found for ' + deptName + '.' + '<br/><br/>' + 
                               '  Please first <a href="/node/add/department_information">add department information</a> for ' + deptName + ' before publishing.';
                var errorHtml = '' +
                                '<div role="contentinfo" aria-label="Error message" class="messages messages--error">' +
                                '  <div role="alert">' +
                                '    <h2 class="visually-hidden">Error message</h2>' + errorMsg +
                                '  </div>' +
                                '</div>';
                $('.region.region-highlighted').append(errorHtml);
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