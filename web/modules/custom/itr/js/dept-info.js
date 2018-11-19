console.log('dept-info.js');
$ = jQuery;

$(window).on('load', function() {
  var titleElem = $('#edit-title-0-value');
  var selectElem = $('#edit-field-department-name');
  
  function updateTitleWithSelectValue(titleElem, selectElem) {
    titleElem.val($(selectElem).find('option[value="' + $(selectElem).val() + '"]').text());
  }

  if(titleElem.val().length > 0) { // there is already a value for title, editing
    var options = $(selectElem).find('option');
    for(var i=0; i<options.length; i++) {
      if($(options[i]).text() == $(titleElem).val()) {
        $(selectElem).val($(options[i]).attr('value'));
        break;
      }
    }
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
});

