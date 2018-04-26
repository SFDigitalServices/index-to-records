$ = jQuery;
console.log('export schedule form');
// $(window).on('load', function() {
//   Drupal.AjaxCommands.prototype.exportScheduleCSVCommand = function(ajax, response, status) {
//     console.log(response);
//   }
// });

function exportSchedule(exportType) {
  var dept = $('#edit-schedule-department').val();
  var sessionUrl = drupalSettings.path.baseUrl + 'session/token';
  var scheduleRetrieveUrl = drupalSettings.path.baseUrl + 'itr_rest_view/schedules/' + dept + '?_format=json';
  var scheduleExportUrl = null;
  var statusEl = null;
  var loaderEl = null;
  var statusLoaderImg = '<img src="' + drupalSettings.path.baseUrl + 'core/themes/stable/images/core/throbber-active.gif' + '"/>';

  switch(exportType.toLowerCase()) {
    case 'csv': {
      scheduleExportUrl = drupalSettings.path.baseUrl + 'itr_rest/schedule/export/csv?_format=json';
      statusEl = '#csv-export-status .message';
      loaderEl = '#csv-export-status .loader';
      break;
    }
    case 'pdf': {
      scheduleExportUrl = drupalSettings.path.baseUrl + 'itr_rest/schedule/export/pdf?_format=json';
      statusEl = '#pdf-export-status .message';
      loaderEl = '#pdf-export-status .loader';
      break; 
    }
    default:
  }

  if(!isNaN(dept)) {
    $('#errors').removeClass('messages messages--error'); // drupal in-built error message classes
    $('#errors').html('');
    $(loaderEl).html(statusLoaderImg);
    $(statusEl).html('Retrieving token');
    $.ajax({
      type: 'GET',
      url: sessionUrl,
      success: function(token) {
        $(statusEl).html('Retrieving schedule');
        $.ajax({
          type: 'GET',
          url: scheduleRetrieveUrl,
          success: function(resp) {
            $(statusEl).html('Exporting');
            var scheduleData = { dept: dept, data: resp };
            $.ajax({
              method: 'POST',
              url: scheduleExportUrl,
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
              },
              data: JSON.stringify(scheduleData),
              success: function(resp) {
                var filedata = resp;
                console.log(filedata);
                if(filedata.length > 0) {
                  $(loaderEl).html('');
                  $(statusEl).html('Schedule successfully exported');
                  $(statusEl).html('<a class="download-link link" target="_blank" href=' + filedata[0].url + '>Download CSV</a>');
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

}

$(window).on('load', function() {
  $('#export-csv').click(function() {
    exportSchedule('csv');
  });

  $('#export-pdf').click(function() {
    exportSchedule('pdf');
  });

})