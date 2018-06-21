$('#register-form-submit').on('click', function(e) {
  e.preventDefault();

  if ($('#consent')["0"].checked && $('#consent2')["0"].checked) {
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
  $('.tokenfield-make').tokenfield();

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
