function validUrl( url ) {
  if(/^(http|https|ftp):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,14}(:[0-9]{1,5})?(\/.*)?$/i.test( url )){
      return true;
  } else {
      return false;
  }
}

function createNewDeviceUrl(event) {

    event.preventDefault();

    // Prepare our variables
    $row = $(this).parents('.input-group');
    $btn = $row.find('.btn');
    $value = $row.find('input.form-control');
    $source = $row.find('.form-control select');
    $device_id = $row.data('device_id');

    // If entered value is not empty and is a valid URL
    if( $value.val() !== '' && validUrl($value.val()) ) {

      // Prevent multiple clicks
      $fields = $row.find('input, select, button');
      $fields.prop('disabled', true);

      // Let's save our data
      $.ajax({
        type: 'POST',
        url: '/device-url',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
          device_id : $device_id,
          url : $value.val(),
          source : $source.val(),
        },
        success: function(data) {

          // Now remove disabled fields
          setTimeout(function(){

            $fields.prop('disabled', false);

          }, 500);

          setTimeout(function(){

            // Clone row ready to be used again
            $row.attr('data-id', data.success).clone().find(".form-control").val("").end().appendTo('.additional-urls');

            // Existing row to be different in appearance and behaviour
            $btn.find('span').text('-');
            $row.removeClass('add-url');
            $row.addClass('save-url');

            // Now focus into the new input
            $('.additional-urls input:last').focus();

          }, 600);

        },
        error: function(error) {

          // Now remove disabled fields
          $fields.prop('disabled', false);

          alert('Cannot create device URL, please try again');

        }
      });

    } else {

      // Make obvious what field has an error and focus into it
      $value.addClass('error');
      $value.focus();
      alert('Please enter a URL to proceed');

    }

}

function editDeviceUrl(event) {

    event.preventDefault();

    // Prepare our variables
    $row = $(this).parents('.input-group');
    $value = $row.find('input.form-control');
    $source = $row.find('.form-control select');
    $id = $row.data('id');

    // If entered value is not empty and is a valid URL
    if( $value.val() !== '' && validUrl($value.val()) ) {

      // Prevent multiple clicks
      $fields = $row.find('input, select, button');
      $fields.prop('disabled', true);

      // Let's save our data
      $.ajax({
        type: 'PUT',
        url: '/device-url/'+$id,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
          url : $value.val(),
          source : $source.val(),
        },
        success: function(data) {

          // Now remove disabled fields
          setTimeout(function(){
            $fields.prop('disabled', false);
          }, 500);

        },
        error: function(error) {

          // Now remove disabled fields
          $fields.prop('disabled', false);

          alert('Cannot create device URL, please try again');

        }
      });

    } else {

      // Make obvious what field has an error and focus into it
      $value.addClass('error');
      $value.focus();
      alert('Please enter a URL to continue');

    }


}

function removeNewDeviceUrl(event) {

    event.preventDefault();

    $row = $(this).parents('.input-group');
    $id = $row.data('id');

    // If entered value is not empty and is a valid URL
    if( confirm('Are you sure you want to remove this URL?') ) {
      // Let's save our data
      $.ajax({
        type: 'DELETE',
        url: '/device-url/'+$id,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function() {

          $row.remove();

        },
        error: function(error) {
          alert('Cannot create device URL, please try again');
        }
      });

    }

}

function clearErrorClass(event) {

    event.preventDefault();
    $(this).removeClass('error');

}

jQuery('.useful-repair-urls').on('click', '.save-url .btn', removeNewDeviceUrl);
jQuery('.useful-repair-urls').on('click', '.add-url .btn', createNewDeviceUrl);
jQuery('.useful-repair-urls').on('keyup', 'input', function(e){
    if(e.keyCode == 13) {
        $(this).trigger("enterKey");
    }
});
jQuery('.useful-repair-urls').on('enterKey', 'input', createNewDeviceUrl);
jQuery('.useful-repair-urls').on('change', '.save-url input', editDeviceUrl);
jQuery('.useful-repair-urls').on('change', '.save-url select', editDeviceUrl);
jQuery('.useful-repair-urls').on('change', '.error', clearErrorClass);

jQuery(function () {

  jQuery(document).on('change', 'select[name=repair_status]', function (e) {
    $status = $(this).val();
    $repair_details = $(this).parents('form').find('.repair-details-edit');
    $spare_parts = $(this).parents('form').find('.spare-parts');
    $barrier = $(this).parents('form').find('.repair-barrier');

    if( $status == 1 ){ // Fixed

      // Reset and hide repair details
      $repair_details.parents('.col-device').addClass('d-none').removeClass('col-device-auto');
      $repair_details.val(0).trigger('change');

      // Show spare parts field
      $spare_parts.parents('.col-device').removeClass('d-none').addClass('col-device-auto');

      // Reset and hide end of life select
      $barrier.parents('.col-device').addClass('d-none').removeClass('col-device-auto');
      $barrier.val(0).trigger('change');

    } else if( $status == 2 ){ // Repairable

      // Show repair details field
      $repair_details.parents('.col-device').removeClass('d-none').addClass('col-device-auto');

      // Show spare parts field
      $spare_parts.parents('.col-device').removeClass('d-none').addClass('col-device-auto');

      // Reset and hide end of life select
      $barrier.parents('.col-device').addClass('d-none').removeClass('col-device-auto');
      $barrier.val(0).trigger('change');

    } else if( $status == 3 ){ // End of life

      // Reset and hide repair details
      $repair_details.parents('.col-device').addClass('d-none').removeClass('col-device-auto');
      $repair_details.val(0).trigger('change');

      // Reset and hide spare parts
      $spare_parts.parents('.col-device').addClass('d-none').removeClass('col-device-auto');
      $spare_parts.val(0).trigger('change');

      // Show end of life field
      $barrier.parents('.col-device').addClass('col-device-auto').removeClass('d-none');

    } else {

      // Reset and hide repair details
      $repair_details.parents('.col-device').addClass('d-none').removeClass('col-device-auto');
      $repair_details.val(0).trigger('change');

      // Reset and hide spare parts
      $spare_parts.parents('.col-device').addClass('d-none').removeClass('col-device-auto');
      $spare_parts.val(0).trigger('change');

      // Reset and hide end of life field
      $barrier.parents('.col-device').addClass('d-none').removeClass('col-device-auto');
      $barrier.val(0).trigger('change');

    }

  });

  $('#devices-table').on('show.bs.collapse', function () {
      $(this).find('.table-device-details').width($(this).parent().width() - 40); 
  });

});
