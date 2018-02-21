$ = jQuery;
console.log('import-schedule');
Drupal.AjaxCommands.prototype.importScheduleCommand = function(ajax, response, status) {
  var schedule = response.data.schedule;
  var dept = response.data.department; // dept taxonomy id
  var sessionUrl = drupalSettings.path.baseUrl + 'session/token';
  var entityCreateUrl = drupalSettings.path.baseUrl + 'entity/node';
  var scheduleRetrieveUrl = drupalSettings.path.baseUrl + 'itr_rest_view/schedules/' + dept;
  var deleteUrl = drupalSettings.path.baseUrl + 'itr_rest/schedule/delete?_format=json';
  // console.log('record count: ' + schedule.length);
  var recCount = schedule.length;
  var start = 1;

  $('#edit-import-schedule-fields').addClass('disabled');
  $('#import-schedule-form input').attr('disabled', true);
  $('#import-schedule-form select').attr('disabled', true);
  $('#import-schedule-form #edit-submit').val('Importing...');
  $('#import-schedule-form #edit-submit').toggle();

  var progressHtml = '';
  progressHtml =  '<div id="import-progress-wrap">';
  progressHtml += '  <div id="import-progress"></div>';
  progressHtml += '</div>';
  progressHtml += '<div id="import-progress-msg"></div>';
  progressHtml += '<div id="import-progress-log"></div>';
  
  $('#import-form-wrapper').after(progressHtml);

  var updateProgressLog = function(msg) {
    $('#import-progress-log').html($('#import-progress-log').html() + msg + '<br/>');
    $('#import-progress-msg').html(msg);
  };

  var updateProgressBar = function(increment, msg) {
    $('#import-progress').css({width: (increment*100) + '%'});
    // if(msg.length > 0) {
    //   $('#import-progress').html('<div>' + msg + '</div>');
    // }
  }

  // first retrieve auth token
  $.ajax({
    type: 'GET',
    url: sessionUrl,
    success: function(token) {
      // token retrieved
      // now delete all records for dept
      // updateProgressLog('auth token retrieved');
      $.ajax({
        type: 'GET',
        url: scheduleRetrieveUrl,
        success: function(resp) {
          updateProgressLog('Records for deletion retrieved');
          var deptRecords = resp;
          var deleteIds = [];
          for(var i=0; i<deptRecords.length; i++) {
            deleteIds.push(deptRecords[i].nid[0].value);
          }
          $.ajax({
              method: 'POST',
              url: deleteUrl,
              headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": token
              },
              data: JSON.stringify(deleteIds),
              success: function(resp) {
                updateProgressLog('Records for ' + $('#edit-schedule-department')[0].options[$('#edit-schedule-department')[0].selectedIndex].innerHTML + ' deleted');
                var p = $.when();
                $.each(schedule, function(idx) {
                  p = p.then(function() {
                    var rec = schedule[idx];
                    var recordNode = {
                      type: [{ target_id: 'record'}],
                      title: [{
                        value: rec.title
                      }],
                      field_division_contact: [{
                        value: rec.division_contact
                      }],
                      field_link: [{
                        value: rec.link
                      }],
                      field_off_site: [{
                        value: rec.off_site
                      }],
                      field_on_site: [{
                        value: rec.on_site
                      }],
                      field_remarks: [{
                        value: rec.remarks
                      }],
                      field_total: [{
                        value: rec.total
                      }],
                      field_department: [{
                        target_id: dept
                      }],
                      field_category: [{ 
                        target_id: rec.category
                      }],
                      field_retention: [{ // TODO: some depts may have multiple retention values - this is probably going to an array of id's
                        target_id: rec.retention
                      }]
                      // TODO: handle division (most depts don't have it, how to enter this using drupal's in-built rest ui to post new content)
                    };
                    return postNode(recordNode, token); 
                  }).done(function(resp) {
                    updateProgressLog('Imported: ' + resp.title[0].value);
                    var increment = (start++)/recCount;
                    if(increment == 1) {
                      updateProgressLog('<p>Imported ' + recCount + ' records successfully.</p><a href="' + drupalSettings.path.baseUrl + 'schedules/' + dept + '">View imported schedule</a><a href="#" id="view-log-link">View log</a>');
                      updateProgressBar(increment, 'Import complete');
                      $('#view-log-link').click(function() {
                        $('#import-progress-log').toggle();
                        var display = $('#import-progress-log').css('display');
                        display === 'block' ? $(this).html('Hide log') : $(this).html('View log');
                      });
                    } else {
                      updateProgressBar(increment);
                    }
                  })
                });
              },
              fail: function(resp) {
                console.log(resp);
              }
          });
        }
      });
    }
  });

  var postNode = function(nodeJson, theToken) {
    return $.ajax({
      method: 'POST',
      url: entityCreateUrl + '?_format=json',
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": theToken
      },
      data: JSON.stringify(nodeJson),
      success: function(node) {
        console.log('success: ' + nodeJson.title[0].value);
      },
      error: function(resp) {
        console.log('error: ' + nodeJson.title[0].value);
      },
      fail: function(resp) {
        console.log('fail: ' + nodeJson.title[0].value);
      }
    })
  }


};