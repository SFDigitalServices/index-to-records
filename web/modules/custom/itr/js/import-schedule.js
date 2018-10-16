$ = jQuery;
console.log('import-schedule');
$(window).on('load', function() {
  Drupal.AjaxCommands.prototype.importScheduleCommand = function(ajax, response, status) {
    var schedule = response.data.schedule;
    var dept = response.data.department; // dept taxonomy id
    var sessionUrl = drupalSettings.path.baseUrl + 'session/token';
    var entityCreateUrl = drupalSettings.path.baseUrl + 'entity/node';
    var scheduleRetrieveUrl = drupalSettings.path.baseUrl + 'itr_rest_view/schedules/' + dept + '?_format=json';
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

    var updateProgressLog = function(msg, flag) {
      if(flag) $('#import-progress-msg').html(msg);
      if(!flag) {
        $('#import-progress-log').html($('#import-progress-log').html() + msg + '<br/>');
        $('#import-progress-msg').html(msg);
      }
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
            console.log(resp);
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
                  console.log('records for delete:', resp);
                  updateProgressLog('Records for ' + $('#edit-schedule-department')[0].options[$('#edit-schedule-department')[0].selectedIndex].innerHTML + ' deleted');

                  var getEntityRefTargets = function(entityRefIds) {
                    var a = [];
                    for(var i=0; i<entityRefIds.length; i++) {
                      a.push({target_id: entityRefIds[i]});
                    }
                    return a;
                  };

                  var checkValues = function(value) {
                    if(value === null || value === 'null' || value === 'undefined' || value === undefined) {
                      value = '';
                    }
                    return value;
                  }

                  var p = $.when();
                  $.each(schedule, function(idx) {
                    p = p.then(function() {
                      var rec = schedule[idx];
                      console.log(rec);
                      // handle category, retention, and division
                      var recordNode = {
                        type: [{ target_id: 'record'}],
                        title: [{
                          value: checkValues(rec.title)
                        }],
                        field_record_title: [{
                          value: checkValues(rec.title)
                        }],
                        field_division_contact: [{
                          value: checkValues(rec.division_contact)
                        }],
                        field_link: [{
                          value: checkValues(rec.link)
                        }],
                        field_off_site: [{
                          value: checkValues(rec.off_site)
                        }],
                        field_on_site: [{
                          value: checkValues(rec.on_site)
                        }],
                        field_remarks: [{
                          value: checkValues(rec.remarks)
                        }],
                        field_total: [{
                          value: checkValues(rec.total)
                        }],
                        field_department: [{
                          target_id: dept
                        }],
                        field_division: getEntityRefTargets(rec.division),
                        field_category: getEntityRefTargets(rec.category),
                        field_retention: getEntityRefTargets(rec.retention)
                      };
                      return postNode(recordNode, token); 
                    }).done(function(resp) {
                      updateProgressLog('Imported: ' + resp.title[0].value);
                      var increment = (start++)/recCount;
                      if(increment == 1) {
                        var finishedMsg = '<p>Imported ' + recCount + ' records successfully.</p>';
                        var finishedLinks = '<a href="' + drupalSettings.path.baseUrl + 'schedules/' + dept + '">View imported schedule</a><a href="#" id="view-log-link">View log</a>';
                        updateProgressLog(finishedMsg);
                        updateProgressLog(finishedLinks, true);
                        updateProgressBar(increment);
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
          console.log('error: ', nodeJson);
        },
        fail: function(resp) {
          console.log('fail: ', nodeJson);
        }
      })
    }


  };
});