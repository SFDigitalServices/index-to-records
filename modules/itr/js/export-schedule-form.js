$ = jQuery;
console.log('export schedule form');
$(window).on('load', function() {
  Drupal.AjaxCommands.prototype.exportScheduleCSVCommand = function(ajax, response, status) {
    console.log(response);
  }
});