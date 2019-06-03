(function($) {
  $(document).ready(function() {
    var titleElem = $('#edit-title-0-value');
    var selectElem = $('#edit-field-department-name');
  
    var sessionUrl = drupalSettings.path.baseUrl + 'session/token';
  
    // get dept categories and divisions
    function getCategoriesAndDivisions(deptId) {
      $('#itr-categories-existing').html('');
      $('#itr-divisions-existing').html('');
      $.ajax({
        type: 'GET',
        url: sessionUrl,
        success: function(token) {
          $.ajax({
            type: 'GET',
            url: '/itr_rest/department/' + deptId + '/category?t=' + (new Date()).getTime(),
            cache: false,
            success: function(data) {
              var categoryHtml = '<ul>';
              for(var i=0; i<data.length; i++) {
                categoryHtml += '<li>' + data[i].name + '</li>';
              }
              categoryHtml +='</ul>'
              $('#itr-categories-existing').html(categoryHtml);
            }
          });
          $.ajax({
            type: 'GET',
            url: '/itr_rest/department/' + deptId + '/division?t=' + (new Date()).getTime(),
            cache: false,
            success: function(data) {
              var divisionHtml = '<ul>';
              for(var i=0; i<data.length; i++) {
                divisionHtml += '<li>' + data[i].name + '</li>';
              }
              divisionHtml += '</ul>';
              $('#itr-divisions-existing').html(divisionHtml);
            }
          })
        }
      })
    }
    
    function updateTitleWithSelectValue(titleElem, selectElem) {
      var deptName = $(selectElem).find('option[value="' + $(selectElem).val() + '"]').text();
      titleElem.val(deptName);
      getCategoriesAndDivisions($(selectElem).val());
    }
  
    if(titleElem.val().length > 0) { // there is already a value for title, editing
      var options = $(selectElem).find('option');
      var deptName = 'No dept selected';
      for(var i=0; i<options.length; i++) {
        if($(options[i]).text() == $(titleElem).val()) {
          $(selectElem).val($(options[i]).attr('value'));
          break;
        }
      }
      getCategoriesAndDivisions($(selectElem).val());
    } else {
      updateTitleWithSelectValue(titleElem, selectElem);
    }
  
    // attach change listener
    $('#edit-field-department-name').change(function() {
      updateTitleWithSelectValue(titleElem, selectElem);
      getCategoriesAndDivisions($(selectElem).val());
    });

    // auto populate email based on name
    $('#edit-field-department-contact-name-0-value').blur(function() {
      $('#edit-field-department-contact-email-0-value').val($(this).val().replace(/\s/g, '.').toLowerCase() + '@sfgov.org');
    });
  
    var spacer = '-';
    $('#edit-field-department-contact-phone-n-0-value').keypress(function(e) {
      var key = event.key.toLowerCase();
      var val = $(this).val();
      if(((key != 'backspace' && key != 'tab' && key.indexOf('arrow') == -1) && isNaN(key))) {
          return false;
      } else {
        if(key != 'backspace' && key != 'tab' && key.indexOf('arrow') == -1) {
          if(val.length <= 11) {
            switch(val.length){
              case 3 : case 7 : 
                val += spacer;
                break;
            }
            $(this).val(val);
          } else {
            return false;
          }
        }
      }
    });
  
    // click add category
    $('#itr-add-category-submit').click(function() {
      $.ajax({
        type: 'GET',
        url: sessionUrl,
        success: function(token) {
          var deptId = $('#edit-field-department-name').val();
          var categoryName = $('#itr-add-category-name').val();
          var dataJson = {deptId: deptId, categories:[categoryName], _format:'json'};
          $.ajax({
            type: 'POST',
            url: '/itr_rest/department/category/add',
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-Token": token
            },
            data: JSON.stringify(dataJson),
            success: function(data) {
              getCategoriesAndDivisions(deptId);
              $('#itr-add-category-name').val('');
            },
            error: function(data) {
              alert('Error adding category');
            },
            fail: function(data) {
              alert('Failure adding category');
            }
          });
        }
      });
    });
  
    // click add division
    $('#itr-add-division-submit').click(function() {
      $.ajax({
        type: 'GET',
        url: sessionUrl,
        success: function(token) {
          var deptId = $('#edit-field-department-name').val();
          var divisionName = $('#itr-add-division-name').val();
          var dataJson = {deptId: deptId, divisions:[divisionName], _format:'json'};
          $.ajax({
            type: 'POST',
            url: '/itr_rest/department/division/add',
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-Token": token
            },
            data: JSON.stringify(dataJson),
            success: function(data) {
              getCategoriesAndDivisions(deptId);
              $('#itr-add-division-name').val('');
            },
            error: function(data) {
              alert('Error adding division');
            },
            fail: function(data) {
              alert('Failure adding division');
            }
          });
        }
      });
    });
  });  
})(jQuery);