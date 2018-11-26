/** Select picker defaults **/

$.fn.selectpicker.Constructor.DEFAULTS.iconBase = 'fa';
$.fn.selectpicker.Constructor.DEFAULTS.tickIcon = 'fa-check';

$(document).ready(function () {

  $('[data-toggle="popover"]').popover(); //enable bootstrap popovers

  $('.selectpicker, .category-select').selectpicker({
    iconBase: 'fa',
    tickIcon: 'fa-check'
  });

  $('button[type=reset]').click(function (e) {
    $($(this).data('form')).find('.selectpicker').val('default').selectpicker('refresh');
  });

  /** Delete option for newly created devices ( /party/manage ) (Host management page) **/
  $(document).on('click', 'a.removebutton', function () {
    $(this).closest('tr').remove();
    return false;
  });

  /** Show/Hide Repairable details ( /party/manage ) (Host management page) **/
  $(document).on('click', '.repairable', function () {
    var detailsWrap = $(this).data('target-details');
    if ($(this).is(':checked')) {
      $(detailsWrap).show();
    }

  });


  $('.repairable').each(function (index) {
    if ($(this).val() == '2' && $(this).is(':checked')) {
      var repairableOptionsSelector = $(this).data('target-details');
      var repairableOptionsElement = $(repairableOptionsSelector);
      repairableOptionsElement.show();
    }
  });

  /*
    $('.file').fileinput({
      data-preview-file-icon="<i class="fa fa-file"></i>",
      data-browse-icon="<i class="fa fa-folder-open"></i> &nbsp;",
      data-upload-icon="<i class="fa fa-upload"></i>"
      browseClass: 'btn btn-primary',
      data-remove-icon="<i class='fa fa-trash'></i>" ,
      removeClass: 'btn btn-default',
      cancelIcon: '<i class="fa fa-ban-circle"></i> ',
      cancelClass: 'btn btn-default',
      data-upload-icon="<i class="fa fa-upload"></i>",
    });
  */
  $("#device-img-modal").on("show.bs.modal", function (e) {
    var img = $(e.relatedTarget).clone();
    console.log(img);
    $(this).find(".modal-body").empty().append(img.find('img').removeClass('device-img'));
  });
  /** Ajax delete of images from party management **/
  $('.device-image-delete').click(function (e) {
    e.preventDefault();
    if (confirm('Are you sure? This cannot be undone.')) {
      var imageWrap = $(this).parent('.device-img-wrap');
      var id = $(this).data('device-image');
      var path = $(this).next().children('img').attr('src');
      var file = path.split('/').pop();
      $.post('/ajax/delete_device_image', { 'id': id, 'file': file }, function (res) {
        if (res == 1) {
          imageWrap.remove();
        }
        else {
          alert('Could not delete this file.');
        }
      });
    }
  });

  /** Add Device Row in Party Management **/
  $('#add-device').click(function (e) {
    e.preventDefault();
    var rows = $('#device-table > tbody > tr').length,
      categories = null,
      restarters = null,
      n = rows + 1;

    $.ajax({
      async: false,
      url: '/ajax/category_list',
      data: {},
      dataType: "html",
      success: function (r) {
        categories = r;
      }
    });


    var tablerow = '<tr class="newdevice">' +
      '<td>' + n + '.</td>' +
      '<td>' +
      '<div class="form-group">' +
      '<select id="device[' + n + '][category]" name="device[' + n + '][category]" class="category-select  form-control" data-live-search="true" tite="Choose category..." required="true">' +
      '<option></option>' +
      categories +
      '<option value="46">None of the above...</option>' +
      '</select>' +
      '</div>' +
      '<div class="form-group hide estimate-box">' +
      '<small>Please input an estimate weight (in kg)</small>' +
      '<div class="input-group">' +
      '<input type="number" step="00.01" min="0" max="99.99" name="device[' + n + '][estimate]" id="device[' + n + '][estimate]" class="form-control" placeholder="Estimate...">' +
      '<span class="input-group-addon">kg</span>' +
      '</div>' +
      '</div>' +
      '</td>' +
      '<td>' +
      '<div class="form-group">' +
      '<input type="text" name="device[' + n + '][brand]" id="device[' + n + '][brand]" class="form-control" placeholder="Brand - e.g. Apple, Dyson">' +
      '</div>' +

      '<div class="form-group">' +
      '<input type="text" name="device[' + n + '][model]" id="device[' + n + '][model]" class="form-control" placeholder="Model - e.g. iPhone 5s, DC50">' +
      '</div>' +
      '<div class="form-group">' +
      '<input type="' + ageInputClass + '" name="device[' + n + '][age]" id="device[' + n + '][age]" class="form-control" placeholder="Age - e.g. 3 years">' +
      '</div>' +
      '</td>' +

      '<td>' +
      '<textarea rows="6" class="form-control" placeholder="Information about the repair.  Where possible, try to provide: fault; cause of fault; and solution/advice given" id="device[' + n + '][problem]" name="device[' + n + '][problem]"></textarea>' +
      '</td>';
    if (feature__device_photos === true) {
      var ageInputClass = feature__device_age ? 'text' : 'hidden';
      tablerow += '<td>' +
        '<div class="form-group">' +
        '<input type="file" class="form-control file" name="device[' + n + '][image]" id="device[' + n + ']image" data-show-upload="false" data-show-caption="true">' +
        '<small>upload a picture of the model/serial no. of the device</small>' +
        '</div>' +
        '</td>';
    }
    tablerow += '<td>' +
      '<div class="form-group">' +
      '<div class="radio">' +
      '<label>' +
      '<input type="radio" name="device[' + n + '][repair_status]" id="device[' + n + '][repair_status_1]" value="1" checked> Fixed' +
      '</label>' +
      '</div>' +
      '<div class="radio">' +
      '<label>' +
      '<input type="radio" class="repairable" data-target-details="#repairable-details[' + n + ']" name="device[' + n + '][repair_status]" id="device[' + n + '][repair_status_2]" value="2"> Repairable' +
      '</label>' +
      '</div>' +
      '<div id="repairable-details[' + n + ']" class="repairable-details">' +
      '<div class="checkbox">' +
      '<label>' +
      '<input type="checkbox" name="device[' + n + '][more_time_needed]" id="device[' + n + '][more_time_needed]" value="1"> More time needed' +
      '</label>' +
      '</div>' +
      '<div class="checkbox">' +
      '<label>' +
      '<input type="checkbox" name="device[' + n + '][professional_help]" id="device[' + n + '][professional_help]" value="1"> Professional help' +
      '</label>' +
      '</div>' +
      '<div class="checkbox">' +
      '<label>' +
      '<input type="checkbox" name="device[' + n + '][do_it_yourself]" id="device[' + n + '][do_it_yourself]" value="1"> Do it yourself' +
      '</label>' +
      '</div>' +
      '</div>' +
      '<div class="radio">' +
      '<label>' +
      '<input type="radio" name="device[' + n + '][repair_status]" id="device[' + n + '][repair_status_3]" value="3"> End-of-life' +
      '</label>' +
      '</div>' +
      '</div>' +
      '</td>' +
      '<td>' +
      '<div class="form-group">' +
      '<div class="checkbox">' +
      '<label>' +
      '<input type="hidden" name="device[' + n + '][spare_parts]" id="device[' + n + '][spare_parts_2]" value="2">' +
      '<input type="checkbox" name="device[' + n + '][spare_parts]" id="device[' + n + '][spare_parts_1]" value="1"> Yes' +
      '</label>' +
      '</div>' +
      '</div>' +
      '</td>' +
      '<td><a class="removebutton btn delete-control"><i class="fa fa-trash"></i></a></td>' +
      '</tr>';

    $('#device-table tbody').append(tablerow);

    $('tr.newdevice .category-select').selectpicker();
    $('tr.newdevice .file').fileinput({
      browseIcon: '&nbsp;<span class="fa fa-upload"></span>&nbsp;',
      removeIcon: '<i class="fa fa-trash"></i> &nbsp;',
      previewFileIcon: '<i class="fa fa-file"></i> &nbsp;',
      icon: '<i class="fa fa-file"></i> &nbsp;',
    });
    $('tr.newdevice .category-select').change(function () {
      var theVal = parseInt($(this).val());
      if (theVal > 0 && theVal != null) {
        if (theVal === 46) {
          $(this).parent().parent().next('.estimate-box').removeClass('hide').addClass('show');
        }
        else {
          $(this).parent().parent().next('.estimate-box').removeClass('show').addClass('hide');
          $(this).parent().parent().next('.estimate-box').children('input').val('');
        }
      }
    });

    /** Show/Hide Repairable details ( /party/manage ) (Host management page) **/
    $('tr.newdevice .repairable').click(function () {
      $(this).parent().parent().next('.repairable-details').addClass('show');
      detailswrap.css({ 'display': 'block' });
    });
  });


  $('#search-groups').change(function () {
    var chainer = $(this).find('option[value]:selected').map(function () {
      return this.text;
    }).get();

    $('#search-parties optgroup').addClass('hidden');
    for (i = 0; i < chainer.length; i++) {
      var label = chainer[i];
      $('#search-parties optgroup[label="' + label + '"]').removeClass('hidden');
    }

    $('#search-parties').selectpicker('refresh');
  });

  if ($('#search-groups option:selected').val() > 0) {
    var chainer = $('#search-groups').find('option[value]:selected').map(function () {
      return this.text;
    }).get();

    $('#search-parties optgroup').addClass('hidden');
    for (i = 0; i < chainer.length; i++) {
      var label = chainer[i];
      $('#search-parties optgroup[label="' + label + '"]').removeClass('hidden');
    }

    $('#search-parties').selectpicker('refresh');
  }


  $('.category-select').change(function () {
    var theVal = parseInt($(this).val());
    if (theVal > 0 && theVal != null) {
      if (theVal === 46) {
        $(this).parent().parent().next('.estimate-box').removeClass('hide').addClass('show');
      }
      else {
        $(this).parent().parent().next('.estimate-box').removeClass('show').addClass('hide');
      }
    }
  });

  /** startup datepickers **/
  if ($('.date, .date input').length > 0) {
    $('.date, .date input').datetimepicker({
      icons: {
        time: "fa fa-clock-o",
        date: "fa fa-calendar",
        up: "fa fa-arrow-up",
        down: "fa fa-arrow-down",
        previous: 'fa fa-chevron-left',
        next: 'fa fa-chevron-right',
        today: 'fa fa-screenshot',
        clear: 'fa fa-trash'
      },
      format: 'DD/MM/YYYY',
      defaultDate: $(this).val()
    });
    $('.time').datetimepicker({
      icons: {
        time: "fa fa-clock-o",
        date: "fa fa-calendar",
        up: "fa fa-arrow-up",
        down: "fa fa-arrow-down",
        previous: 'fa fa-chevron-left',
        next: 'fa fa-chevron-right',
        today: 'fa fa-screenshot',
        clear: 'fa fa-trash'
      },
      format: 'HH:mm',
      defaultDate: $(this).val()

    });

    $('.from-date').datetimepicker({
      useCurrent: false //Important! See issue #1075
    });
    $('.to-date').datetimepicker({
      useCurrent: false //Important! See issue #1075
    });
    $(".from-date").on("dp.change", function (e) {
      $('.to-date').data("DateTimePicker").minDate(e.date);
    });
    $(".to-date").on("dp.change", function (e) {
      $('.from-date').data("DateTimePicker").maxDate(e.date);
    });
  }

  /** linking two times in party creation **/
  $("#start-pc").on("dp.change", function (e) {
    //alert(e);

    var curtime = $(this).val(),
      arrtime = curtime.split(':');
    console.log(arrtime[0] + ' | ' + arrtime[1]);

    $('#end-pc').data("DateTimePicker").date(e.date.add(3, 'h'));
  });



  /** Rich Text Editors **/
  $('.rte').summernote({
    height: 300,
    toolbar: [
      ['cleaner', ['cleaner']], // The Button
      ['style', ['style', 'bold', 'italic', 'underline', 'clear']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['insert', ['link', 'hr']],
      ['misc', ['codeview']]
    ],
    cleaner: {
      notTime: 2400, // Time to display Notifications.
      action: 'paste', // both|button|paste 'button' only cleans via toolbar button, 'paste' only clean when pasting content, both does both options.
      newline: '<br />', // Summernote's default is to use '<p><br></p>'
      notStyle: 'position:absolute;top:0;left:0;right:0', // Position of Notification
      icon: '<i class="note-icon"><span class="fa fa-paintbrush"></span></i>',
      keepHtml: true, // Allow the tags in keepOnlyTags
      keepOnlyTags: ['<p>', '<br>', '<ul>', '<li>', '<b>', '<strong>', '<i>', '<a>', '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>'],
      keepClasses: false, // Remove Classes
      badTags: ['style', 'script', 'applet', 'embed', 'noframes', 'noscript', 'html'], // Remove full tags with contents
      badAttributes: ['style', 'start'] // Remove attributes from remaining tags
    }
  });

  /** Load list of invitable restarters ( /party/create ) **/
  $('.users_group').change(function () { // selectpicker users_group
    var groupId = $(this).val();
    $.getJSON('/ajax/restarters_in_group', { 'group': groupId }, function (data) {
      var checkboxes = '';
      data.forEach(function (e) {
        checkboxes += '<div class="checkbox">' +
          '<label for="users-' + e.id + '">' +
          '<input type="checkbox" checked name="users[]" id="users-' + e.id + '" value="' + e.id + '"> ' +
          e.name + ' (' + e.role + ')' +
          '</label>' +
          '</div>';
      });
      $('.users_group_list').html(checkboxes);
    });
  });

  /** Show/Hide repairable details ( /device/create ) **/
  $('[name="repair_status"]').click(function () {
    if ($(this).is(':checked') && $(this).attr('id') == 'repair_status_2') {

      $('#repairable-details').slideDown('slow');
    }
    else {
      $('#repairable-details').hide('fast');
    }
  });

  /** Show/Hide Repairable details ( /party/manage ) (Host management page) **/
  $('.repairable').click(function () {
    var detailsWrap = $(this).data('target-details');
    if ($(this).is(':checked')) {
      $(detailsWrap).show();
    }
  });

  /** Delete object control **/
  $('.delete-control').click(function (e) {
    e.preventDefault();

    var deleteTarget = $(this).attr('href');
    var deleteControlBox = '<div class="ctrl-box-wrap">' +
      '<div class="ctrl-box">' +
      '<div class="ctrl-box-hdr">' +
      '<h3>Are You Sure?<h3>' +
      '</div>' +
      '<div class="ctrl-box-body">' +
      '<p>Please note that this operation is <strong>irreversible</strong>.</p>' +
      '</div>' +
      '<div class="ctrl-box-foot">' +
      '<a href="' + deleteTarget + '" class="btn btn-danger"><i class="fa fa-trash"></i> Delete</a> &nbsp;' +
      '<a href="#" class="btn btn-default ctrl-box-close"><i class="fa fa-undo"></i> Cancel</a>' +
      '</div>' +
      '</div>' +
      '</div>';

    if ($('.ctrl-box-wrap').length > 0) { $('.ctrl-box-wrap').remove(); }

    $('body').append(deleteControlBox);
    $('.ctrl-box-close').click(function () { $('.ctrl-box-wrap').remove(); });

    return false;
  });

  // file deletion
  $('.remove-image').click(function (e) {
    e.preventDefault();

    var Holder = $(this).parent();
    var image = $(this).data('remove-image');
    var path = $(this).data('image-path');

    $.getJSON($(this).attr('href'), { id: image, path: path }, function () {
      Holder.remove();
    });

    return false;
  });



  /** switch stat bars / host dashboard **/
  $('.switch-view').click(function (e) {
    e.preventDefault();
    var target = $(this).data('target');
    var family = $(this).data('family');

    $('.switch-view').removeClass('active');
    $(this).addClass('active');

    $(family).removeClass('show').addClass('hide');
    $(target).addClass('show');

  });

  /** toggle party views in admin console **/
  $('.party-switch').click(function (e) {
    e.preventDefault();
    var target = $(this).data('target');
    var family = $(this).data('family');

    $('.party-switch').removeClass('active');
    $(this).addClass('active');

    if (target == 'all') {
      $('.party').addClass('show').removeClass('hide');
    }
    else {
      $('.party').removeClass('show').addClass('hide');
      $(target).addClass('show');
    }
  });



  /* manage needed visibility to load correctly charts (host dahsboard) */
  /* $('.charts:first-child').addClass('show');
     $('.charts:not(:first-child)').addClass('hide');
  */
  /** sticky table headers **/
  if ($('#device-table').length > 0) {
    $('#device-table').floatThead();
  }

  /* scrollbar for party list */
  if ($('#party-list').length > 0) {
    $('#party-list').perfectScrollbar();
  }

  $('#register-form-submit').on('click', function(e) {
    e.preventDefault();

    if ( $('#consent_gdpr')["0"].checked && $('#consent_future_data')["0"].checked ) {
      $('#register-form').submit();
    } else {
      alert('You must consent to the use of your data in order to register');
    }

  });

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


function updateVolunteers() {
  var quantity = $('#volunteer_qty').val();
  var event_id = $('#event_id').val();

  $.ajax({
      headers: {
        'X-CSRF-TOKEN': $("input[name='_token']").val()
      },
      type: 'post',
      url: '/party/update-volunteerquantity',
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

  $('#volunteer_qty').on('change', function() {
    updateVolunteers();
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
          alert('test');
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
