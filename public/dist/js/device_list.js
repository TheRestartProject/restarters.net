$(document).ready(function(){
  /** bootgrid tables **/
  $('.bootg').bootgrid({
    formatters: {
        "editLink": function(column, row){
            return "<button data-href=\"/device/ajax_update/" + row.deviceID + "\"  class=\"btn btn-sm modalizer\" data-toggle=\"modal\" data-target=\"#deviceEditor\"><i class=\"fa fa-pencil\"</button>";
        },
        "statusBox": function(column, row){
          var deviceState = '';
          if(row.repairstatus == 1){
            devicestate = 'fixed';
          }
          else if(row.repairstatus == 2){
            devicestate = 'repairable';
          }
          else if(row.repairstatus == 3){
            devicestate = 'end of life';
          }
          else {
            devicestate = 'N.A.';
          }
          return "<div class=\"repair-status repair-status-" + row.repairstatus + "\">" + devicestate + "</div>";
        }
    }
  }).on( "loaded.rs.jquery.bootgrid", function () {
      /* Executes after data is loaded and rendered */
      $(this).find(".modalizer").click(function (e) {
        $($(this).attr("data-target")).removeData('bs.modal');
        $($(this).attr("data-target")).modal({ remote: $(this).data('href') });
        $($(this).attr("data-target")).modal("show");

        $($(this).attr("data-target")).on('loaded.bs.modal', function(){
          
          $('#deviceEditor .selectpicker').selectpicker();
          $('#save-device-update').click(function(e){

            response = {};
            e.preventDefault();

            var theForm = $('#submit-device-update');
            var theUrl = theForm.attr('action');
            var theData = $('#submit-device-update').serialize();

            $.post(theUrl, theData, function(response){
              $('#submit-device-update .message.alert').addClass('alert-' + response.response_type).text(response.message);
              if(response.response_type === 'success'){
                var categoryLabel = $('#deviceEditor .selectpicker').find('option:selected').text();
                var dataRow = $('table.bootg tbody tr[data-row-id=' + response.id + ']');
                dataRow.children('td').eq(1).text(categoryLabel);
                dataRow.children('td').eq(2).text(response.data.brand);
                dataRow.children('td').eq(3).text(response.data.model);
                dataRow.children('td').eq(4).text(response.data.problem);
              }
            }, 'json');
          });
        });
    });
  });
});
