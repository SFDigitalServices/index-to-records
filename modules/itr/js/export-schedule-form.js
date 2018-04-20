$ = jQuery;
console.log('export schedule form');
// $(window).on('load', function() {
//   Drupal.AjaxCommands.prototype.exportScheduleCSVCommand = function(ajax, response, status) {
//     console.log(response);
//   }
// });

$(window).on('load', function() {
  $('#export-csv').click(function() {
    var dept = $('#edit-schedule-department').val();
    var sessionUrl = drupalSettings.path.baseUrl + 'session/token';
    var scheduleRetrieveUrl = drupalSettings.path.baseUrl + 'itr_rest_view/schedules/' + dept + '?_format=json';
    var scheduleExportCSVUrl = drupalSettings.path.baseUrl + 'itr_rest/schedule/export?_format=json';
    if(!isNaN(dept)) {
      $('#errors').removeClass('messages messages--error'); // drupal in-built error message classes
      $('#errors').html('');
      $('#csv-export-status').html('Retrieving auth token');
      $.ajax({
        type: 'GET',
        url: sessionUrl,
        success: function(token) {
          $('#csv-export-status').html('Retrieving schedule');
          $.ajax({
            type: 'GET',
            url: scheduleRetrieveUrl,
            success: function(resp) {
              $('#csv-export-status').html('Exporting');
              var scheduleData = { dept: dept, data: resp };
              $.ajax({
                method: 'POST',
                url: scheduleExportCSVUrl,
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-Token': token
                },
                data: JSON.stringify(scheduleData),
                success: function(resp) {
                  var filedata = resp;
                  console.log(filedata);
                  if(filedata.length > 0) {
                    $('#csv-export-status').html('Schedule successfully exported');
                    $('#csv-export-status').html('<a class="download-link link" href=' + filedata[0].url + '>Download CSV</a>');
                  }
                }
              });
            }
          });
        }
      })
    } else {
      $('#errors').addClass('messages messages--error'); // drupal in-built error message classes
      $('#errors').html('<div>Please select a department</div>');
    }
  })
})