$ = jQuery;
console.log('export-schedule');
var deptId = null;
if(drupalSettings.path.currentQuery) {
  if(drupalSettings.path.currentQuery.field_department_target_id) {
    deptId = drupalSettings.path.currentQuery.field_department_target_id;
  }
} else {
  deptId = drupalSettings.itr.exportSchedule.id;
}
console.log('deptId: ' + deptId);