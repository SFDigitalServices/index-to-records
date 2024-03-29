(function($) {
  var _catSelect = '_none';

  if($('#overlay').length === 0) {
    var overlayDiv = document.createElement('div');
    overlayDiv.id = 'overlay';
    document.body.appendChild(overlayDiv);
  }

  $('#node-record-form #edit-field-category, #node-record-form #edit-field-division').html('<option value="_none"> - Select Department First - </option>');
  $('#edit-field-record-title-0-value').on('keyup', function() {
    var val = $(this).val().length <= 255 ? $(this).val() : $(this).val().substring(0, 255);
    $('#edit-title-0-value').val(val);
  });
  
  $('#record-template-browser #close').click(function() {
    $('#record-template-browser').removeClass('show');
    $(overlay).toggle();
  })
  
  // drupal ajax adds a container div to html command
  var addRecTemplateLink = $('<a id="add-record-template-link" class="hide link" href="#">Add Record Template</a>');
  $('#edit-field-department').after(addRecTemplateLink);
  
  var templates = {
    data: [],
    retention: {},
    use: function(index) {
      if(templates.data.length > 0) {
        var templateData = templates.data[index];
        var formFieldPrefix = 'edit-field-';
        var formFieldSuffix = '-0-value';
        for(var key in templateData) {
          var formKey = key.replace('field_template_', '').replace('_', '-');
          if(formKey == 'retention') {
            // clear out checked values first
            $('input[type="checkbox"]').each(function() {
              $(this).prop('checked', false);
            })
            var retentionVals = templateData[key];
            for(var i=0; i<retentionVals.length; i++) {
              var checkboxFieldSelector = '#edit-field-retention-' + retentionVals[i];
              $(checkboxFieldSelector).prop('checked', true);
            }
          } else {
            switch(formKey) {
              case 'title':
                formKey = 'record-title';
                break;
              case 'rem':
                formKey = 'remarks';
                break;
              default:
                
            }
            var formKeySelector = '#' + formFieldPrefix + formKey + (formKey === 'category' ? '' : formFieldSuffix);
            if(formKey.toLowerCase() === 'category') {
              // check that category currently exists
              var categoryVal = '_none';
              var valueFound = false;
              var categoryName = '';
              $(formKeySelector + ' > option').each(function() {
                var strVal = $(this).html();
                if(strVal.toLowerCase() === templateData[key].toLowerCase()) {
                  categoryVal = $(this).val();
                  valueFound = true;
                  return false;
                } else {
                  categoryName = templateData[key];
                }
              });
              if(!valueFound) {
                $.ajax({
                  type: 'GET',
                  url: drupalSettings.path.baseUrl + 'session/token',
                  success: function(token) {
                    // token retrieved, add category
                    $.ajax({
                      type: 'POST',
                      url: drupalSettings.path.baseUrl + 'itr_rest/department/category/add?_format=json',
                      headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-Token": token
                      },
                      data: JSON.stringify({
                        deptId: $('#edit-field-department').val(),
                        categories: [categoryName]
                      }),
                      success: function(response) {
                        _catSelect = response.categories[0];
                        $('#edit-field-department').trigger('change');
                      }
                    });
                  }
                });
              } else {
                $(formKeySelector).val(categoryVal);
              }
            } else {
              $(formKeySelector).val(templateData[key] ? templateData[key] : '');
              if(formKey === 'record-title') {
                $('#edit-title-0-value').val(templateData[key].substring(0, 255));
              }
            }
          }
        }
        $('#record-template-browser').removeClass('show');
        $('#overlay').toggle();
      }
    }
  };
  
  var checkValue = function(val) {
    if(!val || val === undefined || val === 'undefined' || val === 'false' || val === false) return '';
    return val;
  };
  
  var getRetentionValues = function(retentions) {
    var retentionNames = [];
    for(var i=0; i<retentions.length; i++) {
      retentionNames.push(templates.retention[retentions[i]]);
    }
    return retentionNames.join();
  }
  
  // get retention values and populate templates.retention prop
  $.ajax({
    type: 'GET',
    url: drupalSettings.path.baseUrl + 'itr_rest_view/retention_terms?_format=json',
    success: function(data) {
      for(var i=0; i<data.length; i++) {
        templates.retention[data[i].tid[0].value] = data[i].name[0].value;
      }
    }
  });
  
  $(addRecTemplateLink).click(function(e) {
    e.preventDefault();
    var recordTemplateBrowser = $('#record-template-browser');
    if(templates.data.length <= 0) {
      $.ajax({
        type: 'GET',
        url: drupalSettings.path.baseUrl + 'itr_rest_view/record_templates?_format=json',
        success: function(data) {
          templates.data = data;
          var overlay = $('#overlay');
          $(recordTemplateBrowser).css({'height': ($(window).height() - $('header').height() - 150) + 'px' });
          var recordTemplateBrowserContent = $('#record-template-browser-content');
          var html = '<table><tr>';
          html += '    <th class="template-cell template-category">Category</th>';
          html += '    <th class="template-cell template-title">Title</th>';
          html += '    <th class="template-cell template-link">Link</th>';
          html += '    <th class="template-cell template-retention">Retention</th>';
          html += '    <th class="template-cell template-on-site">On-site</th>';
          html += '    <th class="template-cell template-off-site">Off-site</th>';
          html += '    <th class="template-cell template-total">Total</th>';
          html += '    <th class="template-cell template-remarks">Remarks</th></tr>';
          for(var i=0; i<data.length; i++) {
            var r = data[i];
            html += '<tr id="rec-index_' + i + '" class="record-template-row">';
            html += '  <td class="template-cell template-category">' + r.field_template_category + '</td>';
            html += '  <td class="template-cell template-title">' + checkValue(r.field_template_title) + '</td>';
            html += '  <td class="template-cell template-link">' + checkValue(r.field_template_link) + '</td>';
            html += '  <td class="template-cell template-retention">' + getRetentionValues(r.field_template_retention) + '</td>';
            html += '  <td class="template-cell template-on-site">' + checkValue(r.field_template_on_site) + '</td>';
            html += '  <td class="template-cell template-off-site">' + checkValue(r.field_template_off_site) + '</td>';
            html += '  <td class="template-cell template-total">' + checkValue(r.field_template_total) + '</td>';
            html += '  <td class="template-cell template-remarks">' + checkValue(r.field_template_rem) + '</td>';
            html += '</tr>';
          }
          html += '</table>';
          $(recordTemplateBrowserContent).html(html);
          $(overlay).toggle();
          $(recordTemplateBrowser).addClass('show');
          $('.record-template-row').click(function() {
            var idx = $(this).attr('id').split("_")[1];
            templates.use(idx);
          })
        }
      });
    } else {
      $(overlay).toggle();
      $(recordTemplateBrowser).addClass('show');
    }
  });
  
  $('#edit-field-department').on('change', function() {
    $(addRecTemplateLink).removeClass('hide');
    if(_catSelect !== '_none') {
      $('#edit-field-category').val(_catSelect);
    }
  });
})(jQuery);