console.log('dept-info.js');
$ = jQuery;

$(window).on('load', function() {
  var titleElem = $('#edit-title-0-value');
  var selectElem = $('#edit-field-department-name');

  var sessionUrl = drupalSettings.path.baseUrl + 'session/token';

  // get dept categories and divisions

  function getCategoriesAndDivisions(deptId) {
    $.ajax({
      type: 'GET',
      url: sessionUrl,
      success: function(token) {
        $.ajax({
          type: 'GET',
          url: '/itr_rest/department/' + deptId + '/category',
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
          url: '/itr_rest/department/' + deptId + '/division',
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
  });


  $('#edit-field-department-contact-name-0-value').blur(function() {
    $('#edit-field-department-contact-email-0-value').val($(this).val().replace(/\s/g, '.').toLowerCase() + '@sfgov.org');
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
            console.log(data);
            getCategoriesAndDivisions(deptId);
            $('#itr-add-category-name').val('');
          },
          error: function(data) {
            console.log(data);
          },
          fail: function(data) {
            console.log(data);
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
            console.log(data);
            getCategoriesAndDivisions(deptId);
            $('#itr-add-division-name').val('');
          },
          error: function(data) {
            console.log(data);
          },
          fail: function(data) {
            console.log(data);
          }
        });
      }
    });
  });

});

