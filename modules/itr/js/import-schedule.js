$ = jQuery;
console.log('import-schedule');
Drupal.AjaxCommands.prototype.importScheduleCommand = function(ajax, response, status) {
  var schedule = response.data.schedule;
  var dept = response.data.department; // dept taxonomy id
  console.log('record count: ' + schedule.length);
  $.ajax({
    type: 'GET',
    url: '/index-to-records/session/token',
    success: function(token) {
    //   for(var i=0; i<schedule.length; i++) {
    //     (function(n) {
    //       var rec = schedule[n];
    //       var recordNode = {
    //         type: [{ target_id: 'record'}],
    //         title: [{
    //           value: rec.title
    //         }],
    //         field_division_contact: [{
    //           value: rec.division_contact
    //         }],
    //         field_link: [{
    //           value: rec.link
    //         }],
    //         field_off_site: [{
    //           value: rec.off_site
    //         }],
    //         field_on_site: [{
    //           value: rec.on_site
    //         }],
    //         field_remarks: [{
    //           value: rec.remarks
    //         }],
    //         field_total: [{
    //           value: rec.total
    //         }],
    //         field_department: [{
    //           target_id: dept
    //         }],
    //         field_category: [{ 
    //           target_id: rec.category
    //         }],
    //         field_retention: [{ // TODO: some depts may have multiple retention values - this is probably going to an array of id's
    //           target_id: rec.retention
    //         }]
    //         // TODO: handle division (most depts don't have it, how to enter this using drupal's in-built rest ui to post new content)
    //       };
    //       $.ajax({
    //           method: 'POST',
    //           url: '/index-to-records/entity/node?_format=json',
    //           headers: {
    //             "Content-Type": "application/json",
    //             "X-CSRF-Token": token
    //           },
    //           data: JSON.stringify(recordNode),
    //           success: function(node) {
    //             console.log(node);
    //             setTimeout(function() {
    //               console.log('wait');
    //             },1000);
    //           },
    //           error: function(resp) {
    //             console.log('error: ' + rec.title);
    //           },
    //           fail: function(resp) {
    //             console.log(resp);
    //           }
    //       });
    //     })(i);
    //   }

      var p = $.when();
      $.each(schedule, function(idx) {
        p = p.then(function() {
          var rec = schedule[idx];
          var recordNode = {
            type: [{ target_id: 'record'}],
            title: [{
              value: rec.title
            }],
            field_division_contact: [{
              value: rec.division_contact
            }],
            field_link: [{
              value: rec.link
            }],
            field_off_site: [{
              value: rec.off_site
            }],
            field_on_site: [{
              value: rec.on_site
            }],
            field_remarks: [{
              value: rec.remarks
            }],
            field_total: [{
              value: rec.total
            }],
            field_department: [{
              target_id: dept
            }],
            field_category: [{ 
              target_id: rec.category
            }],
            field_retention: [{ // TODO: some depts may have multiple retention values - this is probably going to an array of id's
              target_id: rec.retention
            }]
            // TODO: handle division (most depts don't have it, how to enter this using drupal's in-built rest ui to post new content)
          };
          return postNode(recordNode, token);
        })
      });

      var postNode = function(nodeJson, theToken) {
        return $.ajax({
          method: 'POST',
          url: '/index-to-records/entity/node?_format=json',
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": theToken
          },
          data: JSON.stringify(nodeJson),
          success: function(node) {
            console.log('success: ' + nodeJson.title[0].value);
          },
          error: function(resp) {
            console.log('error: ' + nodeJson.title[0].value);
          },
          fail: function(resp) {
            console.log('fail: ' + nodeJson.title[0].value);
          }
        })
      }
    }
  });

};