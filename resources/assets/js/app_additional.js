$('#register-form-submit').on('click', function(e) {
  e.preventDefault();

  if ( $('#consent_gdpr')["0"].checked && $('#consent_future_data')["0"].checked ) {
    $('#register-form').submit();
  } else {
    alert('You must consent to the use of your data in order to register');
  }

});

// $('#step-4-form').submit(function(e) {
//   e.preventDefault();
//
//   if ($('#consent1')["0"].checked && $('#consent2')["0"].checked) {
//
//     var step1 = $('#step-1-form').serialize();
//     var step2 = $('#step-2-form').serialize();
//     var step3 = $('#step-3-form').serialize();
//
//     $.ajax({
//         headers: {
//           'X-CSRF-TOKEN': $("input[name='_token']").val()
//         },
//         type: 'post',
//         url: '/user/register',
//         data: {step1 : step1, step2 : step2, step3 : step3},
//         success: function(data) {
//           if (data) {
//             window.location.replace(window.location.origin+"/login");
//           }
//         },
//         error: function(error) {
//           alert(error.message);
//         }
//     });
//
//   } else {
//     alert('You must consent to the use of your data in order to register');
//   }
//
// });

$('#delete-form-submit').on('click', function(e) {
  e.preventDefault();

  if (confirm('You are about to delete your account! Are you sure you wish to continue?')) {
    $('#delete-form').submit();
  }

});

$('#reg_email').on('change', function() {
  $('#email-update').text($(this).val());
});

$(".select2-dropdown").select2({
  placeholder: 'Select an country',
});

$(".select2-tags").select2({
  tags: true,
});

$( document ).ready(function() {
  $('.tokenfield').tokenfield();

  $("#invites_to_volunteers").on("click", function(){
    if (this.checked){
      var event_id = $('#event_id').val();

      $.ajax({
          headers: {
            'X-CSRF-TOKEN': $("input[name='_token']").val()
          },
          type: 'post',
          url: '/party/get-group-emails',
          data: {event_id : event_id},
          success: function(data) {
            var current_items = $('#manual_invite_box').tokenfield('getTokens');
            var new_items = $.parseJSON(data);

            var pop_arr = [];

            current_items.forEach(function(current_item) {
              pop_arr.push(current_item.value);
            });

            new_items.forEach(function(new_item) {
              pop_arr.push(new_item);
            });

            // var populate_arr = new_items.filter(function(obj) { return current_items.indexOf(obj) == -1; });
            // var populate_arr = pop_arr + new_items;

            // console.log($('#manual_invite_box').tokenfield('getTokens'));

            $('#manual_invite_box').tokenfield('setTokens', pop_arr);

            // // console.log("current: "+current_items);
            // // console.log("new: "+new_items);
            // // console.log("pop: "+populate_arr);
            //
            // // console.log(populate_arr.toString() + ","+ current_items.toString());
            // var pop_str = "";
            //
            // current_items.forEach(function(email) {
            //   pop_str += '"'+email+'",\n';
            // });
            //
            // populate_arr.forEach(function(email) {
            //   pop_str += '"'+email+'",\n';
            // });
            //
            // var final_output = pop_str.substring(0, pop_str.length - 2);
            //
            // console.log(final_output);
            //
            // // $("#prepopulate").val('{ "items_new" : [' + final_output + ']\n}');
            //
            // var tokens = $("#manual_invite_box").tokenfield('getTokens');
            //
            // console.log($('#test').tokenfield('getTokens'));
            //
            // console.log(tokens);
            //
            // // $('#manual_invite_box').tokenfield('setTokens', 'blue,red,white');

          },
          error: function(error) {
            console.log('fail');
          }
      });
    }
  });
});

$('#manual_invite_box').on('tokenfield:createtoken', function (event) {
    var existingTokens = $(this).tokenfield('getTokens');
    $.each(existingTokens, function(index, token) {
        if (token.value === event.attrs.value)
            event.preventDefault();
    });
});

function updateParticipants() {
  var quantity = $('#participants_qty').val();
  var event_id = $('#event_id').val();

  $.ajax({
      headers: {
        'X-CSRF-TOKEN': $("input[name='_token']").val()
      },
      type: 'post',
      url: '/party/update-quantity',
      data: {quantity : quantity, event_id : event_id},
      success: function(data) {
        console.log('quantity updated');
      },
      error: function(error) {
        console.log('fail');
      }
  });
}

$( document ).ready(function() {

  $('#participants_qty').on('change', function() {
    updateParticipants();
  });

  $('#repair_status').on('change', function() {
    if ($(this).val() == 2) {
      $('#repair_details').prop('disabled', false);
    } else {
      $('#repair_details').prop('disabled', true);
    }
  });

  $('.checkStatus').on('change', function() {
    var device_id = $(this).data('device');

    if ($(this).val() == 2) {
      $('#repair-info-'+device_id).prop('disabled', false);
    } else {
      $('#repair-info-'+device_id).prop('disabled', true);
    }
  });

  $('#add-device').on('submit', function(e) {
    e.preventDefault();
    // var form_fields = JSON.stringify($(this).serialize());
    // console.log(form_fields);

    var form_array = {
      repair_status:$('#repair_status').val(),
      repair_details:$('#repair_details').val(),
      spare_parts:$('#spare_parts').val(),
      category:$('#category').val(),
      brand:$('#brand').val(),
      model:$('#model').val(),
      age:$('#age').val(),
      problem:$('#problem').val(),
    };

    var event_id = event_id = $('#event_id').val();
    // form_array['repair_status'] = $('#repair_status').val();
    // form_array['repair_details'] = $('#repair_details').val();
    // form_array['spare_parts'] = $('#spare_parts').val();
    // form_array['category'] = $('#category').val();
    // form_array['brand'] = $('#brand').val();
    // form_array['model'] = $('#model').val();
    // form_array['age'] = $('#age').val();
    // form_array['problem'] = $('#problem').val();
    // console.log(form_array.toString());

    $.ajax({
      headers: {
        'X-CSRF-TOKEN': $("input[name='_token']").val()
      },
      type: 'post',
      url: '/device/create',
      data: { form_array : form_array, event_id : event_id },
      success: function(data) {
        if (data.error) {
          alert(data.error);
        }
      },
      error: function(error) {

      }
    });

  });

  $('.edit-device').on('submit', function(e) {
    e.preventDefault();
    var device_id = $(this).data('device');

    // $form = $('form#data-'+device_id);
    // console.log($form[0]);
    // var form_fields = new FormData($form[0]);

    var form_array = {
      repair_status:$('#status-'+device_id).val(),
      repair_details:$('#repair-info-'+device_id).val(),
      spare_parts:$('#spare-parts-'+device_id).val(),
      category:$('#category-'+device_id).val(),
      brand:$('#brand-'+device_id).val(),
      model:$('#model-'+device_id).val(),
      age:$('#age-'+device_id).val(),
      problem:$('#problem-'+device_id).summernote('code'),
      // files:$('#file-'+device_id).val(),
      wiki:$('#wiki-'+device_id).checked,
    };

    var event_id = event_id = $('#event_id').val();

    $.ajax({
      headers: {
        'X-CSRF-TOKEN': $("input[name='_token']").val()
      },
      type: 'post',
      url: '/device/edit/'+device_id,
      data: { form_array : form_array, event_id : event_id },
      success: function(data) {
        if (data.error) {
          alert(data.error);
        }
      },
      error: function(error) {

      }
    });

  });

  $('#description').on('summernote.change', function(e) {
    $('#free_text').val($('#description').summernote('code'));
  });
});
