(function($) {
  $(document).ready(function() {
    Drupal.AjaxCommands.prototype.importScheduleCommand = function(ajax, response, status) {
      var schedule = response.data.schedule;
      var dept = response.data.department; // dept taxonomy id
      var sessionUrl = drupalSettings.path.baseUrl + 'session/token';
      var entityCreateUrl = drupalSettings.path.baseUrl + 'node';
      var scheduleRetrieveUrl = drupalSettings.path.baseUrl + 'itr_rest_view/schedules/' + dept + '?_format=json';
      var deleteUrl = drupalSettings.path.baseUrl + 'itr_rest/schedule/delete?_format=json';
      var recCount = schedule.length;
  
      $('#edit-import-schedule-fields').addClass('disabled');
      $('#import-schedule-form input').attr('disabled', true);
      $('#import-schedule-form select').attr('disabled', true);
      $('#import-schedule-form #edit-submit').val('Importing...');
      $('#import-schedule-form #edit-submit').toggle();
  
      var progressHtml = '';
      progressHtml =  '<div id="import-progress-wrap">';
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
  
      var updateProgressBar = function(recCount, success) {
        var progressBarWrap = $('#import-progress-wrap');
        var elemSize = 100/recCount;
        var elemMargin = 0;
        var elemClass = success ? 'import-progress-indicator-success' : 'import-progress-indicator-error';
        var elem = '<div style="width:' + (elemSize - (elemMargin * 2)) + '%; margin: 0 ' + elemMargin + '%" class="' + elemClass + '"></div>';
        $(progressBarWrap).append(elem);
        window.getComputedStyle($(elem)[0]).width;
      }
  
      var deptName = $('#edit-schedule-department')[0].options[$('#edit-schedule-department')[0].selectedIndex].innerHTML;
  
      // first retrieve auth token
      $.ajax({
        type: 'GET',
        url: sessionUrl,
        success: function(token) {
          // token retrieved
          // now delete all records for dept
          updateProgressLog('Retrieving records for ' + deptName + ' for deletion');
          // get records for deletion
          $.ajax({
            type: 'GET',
            url: scheduleRetrieveUrl,
            success: function(resp) {
              updateProgressLog('Records for deletion for ' + deptName + ' retrieved');
              var deptRecords = resp;
              var deleteIds = [];
              for(var i=0; i<deptRecords.length; i++) {
                deleteIds.push(deptRecords[i].nid[0].value);
              }
  
              // now delete them
              $.ajax({
                method: 'POST',
                url: deleteUrl,
                headers: {
                  "Content-Type": "application/json",
                  "X-CSRF-Token": token
                },
                data: JSON.stringify(deleteIds),
                success: function(resp) {
                  // records successfully deleted
                  updateProgressLog('Records for ' + deptName + ' deleted');

                  var p = $.when();
                  var successCount = 0;
                  var failCount = 0;

                  // now import the schedule
                  var getEntityRefTargets = function(entityRefIds) {
                    var a = [];
                    for(var i=0; i<entityRefIds.length; i++) {
                      a.push({target_id: entityRefIds[i]});
                    }
                    return a;
                  };

                  var checkValues = function(value, truncate) {
                    if(value === null || value === 'null' || value === 'undefined' || value === undefined) {
                      value = '';
                    } else if(value.length > 255 && truncate) {
                      value = value.substr(0,255);
                    }
                    return value;
                  }

                  var processRecord = function(rec, index) {
                    var defer = $.Deferred();
                    var errorMsg = '';
                    if(!rec.title) {
                      errorMsg += 'Missing title.  ';
                    }
                    if(!rec.retention) {
                      errorMsg += 'Missing retention.  ';
                    }
                    if(!rec.category) {
                      errorMsg += 'Missing category.  ';
                    }
                    if(errorMsg.length > 0) {
                      updateProgressLog('<span class="import-error-msg">Skipped record at index ' + index + '.  ' + rec.title + '.' + errorMsg + '</span>');
                      updateProgressBar(schedule.length, false);
                      failCount = failCount + 1;
                      defer.resolve();
                    } else {

                      // this is the format that entity create api is expecting
                      var recordNode = {
                        type: [{ target_id: 'record'}],
                        title: [{
                          value: checkValues(rec.title, true)
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

                      // create the record
                      $.ajax({
                        method: 'POST',
                        url: entityCreateUrl + '?_format=json',
                        headers: {
                          "Content-Type": "application/json",
                          "X-CSRF-Token": token
                        },
                        data: JSON.stringify(recordNode),
                        success: function(node) {
                          successCount++;
                          updateProgressLog('Imported: ' + node.title[0].value);
                          updateProgressBar(recCount, true);
                          defer.resolve();
                        },
                        error: function(resp) {
                          defer.resolve();
                        },
                        fail: function(resp) {
                          defer.resolve();
                        }
                      });
                    }
                    return $.when(defer).done().promise();
                  };

                  var deferred = $.Deferred().resolve(); // start the defer chain
                  $.each(schedule, function(idx) {
                    var rec = schedule[idx];
                    deferred = deferred.then(function() {
                      return processRecord(rec, idx);
                    });
                  });

                  // all things done
                  deferred.done(function() {
                    var finishedMsg = '<div class="import-stats"><p class="import-success-msg">Imported ' + successCount + ' record(s) successfully.</p>';
                    finishedMsg += '<p class="import-error-msg">Could not import ' + failCount + ' record(s)</p></div>';
                    var finishedLinks = '<a href="' + drupalSettings.path.baseUrl + 'schedules/' + dept + '">View imported schedule</a><a href="#" id="view-log-link">View log</a>';
                    updateProgressLog(finishedMsg);
                    updateProgressLog(finishedLinks, true);
                    $('#view-log-link').click(function() {
                      $('#import-progress-log').toggle();
                      var display = $('#import-progress-log').css('display');
                      display === 'block' ? $(this).html('Hide log') : $(this).html('View log');
                    });
                  });
                },
                error: function(resp) {
                  alert('Error deleting records');
                },
                fail: function(resp) {
                  alert('Failure deleting records');
                }
              });
            },
            error: function(resp) {
              alert('Error retrieving records');
            },
            fail: function(resp) {
              alert('Failure retrieving records');
            }
          });
        }
      });
    };
  });
})(jQuery);