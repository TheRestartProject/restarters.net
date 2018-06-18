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
