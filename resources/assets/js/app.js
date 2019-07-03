/**
* First we will load all of this project's JavaScript dependencies which
* includes Vue and other libraries. It is a great starting point when
* building robust, powerful web applications using Vue and Laravel.
*/

require('./bootstrap');
require('./bootstrap-tokenfield.min');
require('./bootstrap-sortable.js');
require('select2');
require('slick-carousel');
require('summernote');
require('ekko-lightbox');
require('bootstrap4-datetimepicker');
require('./misc/notifications');
require('./misc/device');
require('leaflet');
window.Dropzone = require('dropzone');
window.Tokenfield = require("tokenfield");

if ( jQuery('.slideshow').length > 0 ) {
  jQuery('.slideshow').slick({
    dots: true,
    arrows:true,
    infinite: false,
    responsive: [
      {
        breakpoint: 1024,
        settings: {
          arrows: false
        }
      },
      {
        breakpoint: 480,
        settings: {
          autoplay: true,
          arrows: false
        }
      }
    ]
  });
}

function validateForm() {

  var form = jQuery('#step-2');
  var validCount = 0;

  var validation = Array.prototype.filter.call(form, function (form) {

    // Convert to Array, as IE doesn't support forEach on NodeList.
    // See e.g. https://github.com/babel/babel/issues/6511#issuecomment-338076009
    var requiredFields = form.querySelectorAll('[required]');
    var requiredFieldsArray = Array.from(requiredFields);
    requiredFieldsArray.forEach(element => {

      console.log(element.checkValidity());

      if (element.checkValidity() === false ) {

        if (element.tagName === 'SELECT') {
          element.parentNode.classList.add('is-invalid');
        } else {
          element.classList.add('is-invalid');
        }

        validCount--;

      } else {

        if (element.tagName === 'SELECT') {
          element.parentNode.classList.remove('is-invalid');
        } else {
          element.classList.remove('is-invalid');
        }

        validCount++;
      }

    });

    if ( validCount !== jQuery('#step-2').find('input,select').filter('[required]:visible').length ) {
      return false;

    } else if ( jQuery('#password').length > 0 && jQuery('#password').val().length < 6 ) {

      jQuery('#password').addClass('is-invalid');
      jQuery('#password-confirm').addClass('is-invalid');
      return false;

    } else if ( jQuery('#password').length > 0 && jQuery('#password').val() !== jQuery('#password-confirm').val() ) {

      jQuery('#password').addClass('is-invalid');
      jQuery('#password-confirm').addClass('is-invalid');
      return false;

    } else {

      jQuery('#password').removeClass('is-invalid');
      jQuery('#password-confirm').removeClass('is-invalid');

      jQuery('.registration__step').removeClass('registration__step--active');
      jQuery('#step-3').addClass('registration__step--active');

    }

  });




}

function formProcess(e) {
  var target = jQuery(this).data('target');
  var targetLabel = document.getElementById(target+'-form-label');
  e.preventDefault();

  jQuery('.btn-next').attr('aria-expanded', 'false');
  jQuery(this).attr('aria-expanded', 'true');
  if ( jQuery('#step-2.registration__step--active').length > 0 ) {
    validateForm();
  } else {
    jQuery('.registration__step').removeClass('registration__step--active');
    jQuery('#' + target).addClass('registration__step--active');
    if (targetLabel) { targetLabel.scrollIntoView(500) }
  }

}

function formProcessPrev(e) {
  var target = jQuery(this).data('target');
  var targetLabel = document.getElementById(target+'-form-label');
  e.preventDefault();

  jQuery('.registration__step').removeClass('registration__step--active');
  jQuery('.btn-next').attr('aria-expanded','false');
  jQuery('#' + target).addClass('registration__step--active');
  jQuery(this).attr('aria-expanded', 'true');

  if (targetLabel) { console.log(targetLabel); targetLabel.scrollIntoView(500) }

}

jQuery('.btn-next').on('click',formProcess);
jQuery('.registration__prev').on('click', formProcessPrev);

function registration() {

  if ( jQuery('section.registration').length > 0 && jQuery('.alert.alert-danger').length > 0 && jQuery('.is-invalid').length > 0 ) {

    jQuery('.registration__step').removeClass('registration__step--active');
    jQuery('.is-invalid').first().parents('.registration__step').addClass('registration__step--active');

  }

}

function onboarding() {
  if ( jQuery('body.onboarding').length > 0 ) {

    jQuery('#onboarding').modal('show');

    jQuery('#onboarding').on('shown.bs.modal', function () {
      jQuery('.modal-slideshow').slick({
        dots: true, arrows: true, infinite: false,
        prevArrow: '.modal-prev',
        nextArrow: '.modal-next'
      });
    });

    //if ( jQuery('.slick-initialized').length > 0 ) {

    jQuery('#onboarding').on('beforeChange', function (event, slick, currentSlide, nextSlide) {
      if (nextSlide === 2) {
        jQuery('.modal-finished').addClass('modal-finished--visible');
        jQuery('.close').addClass('close--visible');
      } else {
        jQuery('.modal-finished').removeClass('modal-finished--visible');
        jQuery('.close').removeClass('close--visible');
      }
    });

    //}


    jQuery('#onboarding').on('hide.bs.modal', function (e) {
      $.ajax({
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        },
        type: 'get',
        url: '/user/onboarding-complete',
      });
      jQuery('.modal-slideshow').slick('destroy');
    });

  }
}

function serialize(tokenfield) {
  var items = tokenfield.getItems();
  //console.log(items);
  var prop;
  var data = {};
  items.forEach(function (item) {
    if (item.isNew) {
      prop = tokenfield._options.newItemName;
    } else {
      prop = tokenfield._options.itemName;
    }
    if (typeof data[prop] === 'undefined') {
      data[prop] = [];
    }
    if (item.isNew) {
      data[prop].push(item.name);
    } else {
      data[prop].push(item[tokenfield._options.itemValue]);
    }
  });
  return data;
}

// function initTokenfields() {
//     if ( document.querySelectorAll('.tokenfield').length > 0 ) {
//
//         var tokens = document.querySelector('#prepopulate');
//
//         var tf = new Tokenfield({
//             el: document.querySelector('.tokenfield')
//         });
//
//         tf.on('change', function () {
//             var out = JSON.stringify(serialize(tf), null, 2);
//             tokens.value = out;
//         });
//
//     }
//
// }

var placeSearch, autocomplete;
var componentForm = {
  street_number: 'short_name',
  route: 'long_name',
  locality: 'long_name',
  administrative_area_level_1: 'short_name',
  country: 'long_name',
  postal_code: 'short_name'
};

function initAutocomplete() {
  // Create the autocomplete object, restricting the search to geographical
  // location types.
  autocomplete = new google.maps.places.Autocomplete(
    /** @type {!HTMLInputElement} */(document.getElementById('autocomplete')),
    { types: ['geocode'] });

    // When the user selects an address from the dropdown, populate the address
    // fields in the form.
    autocomplete.addListener('place_changed', fillInAddress);
  }

  function fillInAddress() {
    // Get the place details from the autocomplete object.
    var place = autocomplete.getPlace();

    for (var component in componentForm) {
      document.getElementById(component).value = '';
      document.getElementById(component).disabled = false;
    }

    // Get each component of the address from the place details
    // and fill the corresponding field on the form.
    for (var i = 0; i < place.address_components.length; i++) {
      var addressType = place.address_components[i].types[0];
      if (componentForm[addressType]) {
        var val = place.address_components[i][componentForm[addressType]];
        document.getElementById(addressType).value = val;
      }
    }

    // Initialise map
    var map = new google.maps.Map(document.getElementById('map-plugin'), {
      center: { lat: -33.8688, lng: 151.2195 },
      zoom: 13,
      disableDefaultUI: true
    });

    // Bind the map's bounds (viewport) property to the autocomplete object,
    // so that the autocomplete requests use the current map bounds for the
    // bounds option in the request.
    autocomplete.bindTo('bounds', map);

    var marker = new google.maps.Marker({
      map: map,
      anchorPoint: new google.maps.Point(0, -29)
    });

    marker.setVisible(false);

    if (!place.geometry) {
      // User entered the name of a Place that was not suggested and
      // pressed the Enter key, or the Place Details request failed.
      window.alert("No details available for input: '" + place.name + "'");
      return;
    }

    if (place.geometry.viewport) {
      map.fitBounds(place.geometry.viewport);
    } else {
      map.setCenter(place.geometry.location);
      map.setZoom(17);
    }
    marker.setPosition(place.geometry.location);
    marker.setVisible(true);

    var address = '';
    if (place.address_components) {
      address = [
        (place.address_components[0] && place.address_components[0].short_name || ''),
        (place.address_components[1] && place.address_components[1].short_name || ''),
        (place.address_components[2] && place.address_components[2].short_name || '')
      ].join(' ');
    }


  }

  function geolocate() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (position) {
        var geolocation = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };
        var circle = new google.maps.Circle({
          center: geolocation,
          radius: position.coords.accuracy
        });
        autocomplete.setBounds(circle.getBounds());
      });
    }
  }

  function groupsMap() {
    if (jQuery('.field-geolocate').length > 0 ) {

      initAutocomplete();

      // var map, places, infoWindow;
      // var markers = [];
      // var autocomplete;
      // var countryRestrict = { 'country': 'uk' };
      // var MARKER_PATH = 'https://developers.google.com/maps/documentation/javascript/images/marker_green';
      // var hostnameRegexp = new RegExp('^https?://.+?/');

      // map = new google.maps.Map(document.getElementById('map-plugin'), {
      //     zoom: countries['uk'].zoom,
      //     center: countries['uk'].center,
      //     mapTypeControl: false,
      //     panControl: false,
      //     zoomControl: false,
      //     streetViewControl: false
      // });

    }
  }

  function truncate() {

    if (jQuery('.truncate').length > 0) {
      jQuery('.truncate').each(function () {
        jQuery(this).parent().addClass('truncated');
      });
    }

    var button = jQuery('.truncate__button');
    button.on('click', function (e) {
      e.preventDefault();

      if (jQuery(this).parent().hasClass('truncated')) {
        jQuery(this).parent().removeClass('truncated');
        jQuery(this).find('span').text('Show less');
      } else {
        jQuery(this).parent().addClass('truncated');
        jQuery(this).find('span').text('Read more');
      }
    });
  }

  function eventsMap() {
    if ( jQuery('#event-map').length > 0 ) {

      const mapObject = document.querySelector('#event-map');

      let latitude = parseFloat(mapObject.dataset.latitude);
      let longitude = parseFloat(mapObject.dataset.longitude);
      let zoom = parseFloat(mapObject.dataset.zoom);

      if( latitude && longitude ){
          let map = L.map('event-map').setView([latitude, longitude], zoom);

          L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}{r}.png', {
              attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://carto.com/attribution">CARTO</a>'
          }).addTo(map);

          var icon = new L.Icon.Default();
          icon.options.shadowSize = [0,0];
          L.marker([latitude, longitude], {icon:icon}).addTo(map);
      }
    }
  }

  function textEditor() {

    if (jQuery('.rte').length > 0){
      jQuery('.rte').summernote({
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
    }


  }

  function updateParticipants() {

    var quantity = $('#participants_qty').val();
    var event_id = $('#event_id').val();

    $.ajax({
      headers: {
        'X-CSRF-TOKEN': $("input[name='_token']").val()
      },
      type: 'post',
      url: '/party/update-quantity',
      data: {
        quantity : quantity,
        event_id : event_id
      },
      datatype: 'json',
      success: function(json) {
        if( json.success ){
          console.log('quantity updated');
        } else {
          alert('You are not a host of this event');
        }
      },
      error: function(error) {
        alert('Something has gone wrong');
      }
    });

  }

  var current_volunteers = parseInt(jQuery('#attended-counter').html());

  function updateVolunteers() {

    var quantity = $('#volunteer_qty').val();
    var event_id = $('#event_id').val();

    // Let's see whether there's been a manual change
    if( quantity != current_volunteers ){
      $("#warning_volunteers_message").fadeIn(200);
    } else {
      $("#warning_volunteers_message").hide();
    }

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

  function numericInputs() {

    jQuery('.decrease').on('click', function (e) {

      e.preventDefault();

      var value = parseInt(jQuery(this).parent().find('input[type="number"]').val());

      if (value > 0) {
        jQuery(this).parent().find('input[type="number"]').val(value - 1);
      }
      updateParticipants();
    });


    jQuery('.increase').on('click', function (e) {

      e.preventDefault();

      var value = parseInt(jQuery(this).parent().find('input[type="number"]').val());

      jQuery(this).parent().find('input[type="number"]').val(value + 1);
      updateParticipants();
    });

    jQuery('.decreaseVolunteers').on('click', function (e) {

      e.preventDefault();

      var value = parseInt(jQuery(this).parent().find('input[type="number"]').val());
      if (value > 0) {
        jQuery(this).parent().find('input[type="number"]').val(value - 1);
      }

      updateVolunteers();

    });

    jQuery('.increaseVolunteers').on('click', function (e) {

      e.preventDefault();

      var value = parseInt(jQuery(this).parent().find('input[type="number"]').val());
      jQuery(this).parent().find('input[type="number"]').val(value + 1);

      updateVolunteers();

    });
  }

  function removeUser() {

    user_id = jQuery(this).data('remove-volunteer');
    event_id = jQuery(this).data('event-id');
    type = jQuery(this).data('type');
    counter = jQuery('#'+type+'-counter');
    current_count = parseInt(counter.text());

    $.ajax({
      headers: {
        'X-CSRF-TOKEN': $("input[name='_token']").val()
      },
      type: 'post',
      url: '/party/remove-volunteer',
      data: {
        user_id : user_id,
        event_id : event_id
      },
      datatype: 'json',
      success: function(json) {
        if( json.success ){
          jQuery('.volunteer-' + user_id).fadeOut();
          jQuery('#'+type+'-counter').text();
          counter.text( current_count - 1 );
        } else {
          alert('Something has gone wrong');
        }
      },
      error: function(error) {
        alert('Something has gone wrong');
      }
    });

  }

  function nestedTable() {

    jQuery('.table-row-details').each(function(){

      jQuery(this).on('show.bs.collapse', function () {
        jQuery(this).prev('tr').addClass('active-row');
        //jQuery(this).prev('tr').find('.row-button')
      })
      jQuery(this).on('hide.bs.collapse', function () {
        jQuery(this).prev('tr').removeClass('active-row');
      })

    });


  }

  function loadDropzones() {

    if (jQuery(".dropzoneEl").length > 0 ) {
      console.log(jQuery(".dropzoneEl").length);

      var field1 = jQuery('.dropzone').data('field1');
      var field2 = jQuery('.dropzone').data('field2');

      var preview = ".uploads";
      var dropzone_id = ".dropzoneEl";
      var prefix = '';

      $(".dropzoneEl").each(function( index ) {

        $dropzone = $(this);

        if ($(this).data('deviceid') !== undefined) {
          prefix = '-'+$(this).data('deviceid');
        } else {
          prefix = '';
        }

        // console.log($('#dropzoneEl-' + $(this).data('deviceid'))["0"].dropzone);
        if (typeof $('#dropzoneEl-' + $(this).data('deviceid'))["0"].dropzone != "undefined") {
          // Do nothing
          // console.log('nothing');
        } else {

          var instanceDropzone = new Dropzone('#dropzoneEl-' + $(this).data('deviceid'), {
            paramName: "file", // The name that will be used to transfer the file
            maxFilesize: 2,
            dictDefaultMessage: '',
            parallelUploads: 100,
            uploadMultiple: true,
            createImageThumbnails: true,
            thumbnailWidth: 120,
            thumbnailHeight: 120,
            addRemoveLinks: true,
            previewsContainer: ".uploads-" + $(this).data('deviceid'),
            init: function () {
                this.on("success", function(file) { alert("Image added!"); });
                this.on("error", function(file, errorMessage) {
                    this.removeFile(file);
                    alert("Error: " + errorMessage);
                });
                $dropzone.find(".dz-message").append('<span>' + field1 + '</span><small>' + field2 + '</small>');
            }
          });
        }
      });

    }

    if (jQuery("#dropzoneSingleEl").length > 0) {

      var field1 = jQuery('#dropzoneSingleEl').data('field1');
      var field2 = jQuery('#dropzoneSingleEl').data('field2');

      var instanceDropzone = new Dropzone("#dropzoneSingleEl", {
        init: function () {
          jQuery(".dz-message").find('span').text(field1);
          jQuery(".dz-message").append('<small>'+field2+'</small>');
        },
        paramName: "file", // The name that will be used to transfer the file
        // maxFilesize: 4,
        maxFiles: 1,
        uploadMultiple: false,
        createImageThumbnails: true,
        addRemoveLinks: true,
        thumbnailWidth: 60,
        thumbnailHeight: 60,
        thumbnailMethod: "contain",
        previewsContainer: ".uploads-" + $("#dropzoneSingleEl").data('deviceid'),
      });

    }

    if (jQuery("#dropzoneSingleEl-create").length > 0) {

      var field1 = jQuery('#dropzoneSingleEl-create').data('field1');
      var field2 = jQuery('#dropzoneSingleEl-create').data('field2');

      var instanceDropzone = new Dropzone("#dropzoneSingleEl-create", {
        init: function () {
          jQuery(".dz-message").find('span').text(field1);
          jQuery(".dz-message").append('<small>'+field2+'</small>');
        },
        paramName: "file", // The name that will be used to transfer the file
        // maxFilesize: 4,
        autoProcessQueue: false,
        maxFiles: 1,
        uploadMultiple: false,
        createImageThumbnails: true,
        addRemoveLinks: true,
        thumbnailWidth: 60,
        thumbnailHeight: 60,
        thumbnailMethod: "contain",
        previewsContainer: ".uploads",
      });

    }
  }

  function resetForm (e) {
    e.preventDefault();
    var attr = jQuery(this).data('form');
    var form = jQuery('#' + attr);
    form[0].reset();

    if (form.find('select').length > 0 ) {
      form.find('select').val('').trigger('change');
    }

  }

  tag_options = {
    tags: true,
    createTag: function (params) {
      return null;
    }
  }

  repair_barrier_options = {
    placeholder: "-- Choose barriers to repair --"
  }

  tag_options_with_input = {
    tags: true,
    minimumInputLength: 2,
    formatInputTooShort: "Type a brand name",
    language: {
      inputTooShort: function inputTooShort() {
        return 'Type a brand name';
      }
    },
    minimumInputLength: 2,
    createTag: function (params) {
      return {
        id: params.term,
        text: params.term,
        newOption: true
      }
    }
  }

  function select2Fields($target = false) {


    if( $target === false ){

      jQuery('.select2').select2();
      jQuery('.select2-repair-barrier').select2(repair_barrier_options);
      jQuery('.select2-tags').select2(tag_options);
      jQuery(".select2-with-input").select2(tag_options_with_input);
      jQuery(".select2-with-input-group").select2({
        width: 'auto',
    		dropdownAutoWidth: true,
    		allowClear: true,
      });

    } else {

      $target.find('.select2').select2();
      $target.find('.select2-repair-barrier').select2(repair_barrier_options);
      $target.find('.select2-tags').select2(tag_options);
      $target.find(".select2-with-input").select2(tag_options_with_input);
      $target.find(".select2-with-input-group").select2({
        width: 'auto',
    		dropdownAutoWidth: true,
    		allowClear: true,
      });

    }


    // $(document).on('focus', '.select2.select2-container', function (e) {
    //   // only open on original attempt - close focus event should not fire open
    //   if (e.originalEvent && $(this).find(".select2-selection--single").length > 0) {
    //     $(this).siblings('select').select2('open');
    //   }
    // });

  }

  function customDates() {


    if (jQuery('input[type="date"], .date, .date input').length > 0) {

      jQuery('input[type="date"], .date, .date input').datetimepicker({
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
        format: 'dd/mm/yyyy',
        defaultDate: jQuery(this).val()
      });
      jQuery('.time').datetimepicker({
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
        defaultDate: jQuery(this).val()

      });

      jQuery('.from-date').datetimepicker({
        useCurrent: false //Important! See issue #1075
      });
      jQuery('.to-date').datetimepicker({
        useCurrent: false //Important! See issue #1075
      });
      jQuery(".from-date").on("dp.change", function (e) {
        jQuery('.to-date').data("DateTimePicker").minDate(e.date);
      });
      jQuery(".to-date").on("dp.change", function (e) {
        jQuery('.from-date').data("DateTimePicker").maxDate(e.date);
      });
    }

  }

  Dropzone.autoDiscover = false;
  registration();
  onboarding();
  //initTokenfields();
  textEditor();
  numericInputs();
  eventsMap();
  truncate();
  nestedTable();
  select2Fields();
  //customDates();

  jQuery(function () {


    // jQuery('.dropdown-menu').on('hidden.bs.collapse', function () {
    //     console.log('eve');
    // });

    jQuery('.users-list').find('[data-toggle="popover"]').popover();

    jQuery('.users-list').find('[data-toggle="popover"]').on('click', function (e) {
      jQuery('.users-list').find('[data-toggle="popover"]').not(this).popover('hide');
    });

    jQuery('.table:not(.table-devices)').find('[data-toggle="popover"]').popover({
      template: '<div class="popover popover__table" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>',
      placement:'top'
    })

    jQuery('.table-devices').find('[data-toggle="popover"]').popover();

    jQuery('.table').find('[data-toggle="popover"]').on('click', function (e) {
      jQuery('.table').find('[data-toggle="popover"]').not(this).popover('hide');
    });

    // jQuery(document).on('change', '.repair_status', function (e) {
    //   $value = jQuery(this).val();
    //   $field = jQuery(this).parents('td').find('.repair_details');
    //   if( $value == 2 ){
    //     $field.prop('disabled', false);
    //     $field.parents('.repair-more').removeClass('d-none');
    //   } else {
    //     $field.val(0);
    //     $field.trigger('change');
    //     $field.prop('disabled', true);
    //     $field.parents('.repair-more').addClass('d-none');
    //   }
    // });

    jQuery(document).on('change', '.category', function (e) {
      $value = parseInt(jQuery(this).val());
      $field = jQuery(this).parents('td').find('.weight');
      if( $value === 46 || $value === '' ){
        $field.prop('disabled', false);
        $field.parents('.display-weight').removeClass('d-none');
      } else {
        $field.val('');
        $field.trigger('change');
        $field.prop('disabled', true);
        $field.parents('.display-weight').addClass('d-none');
      }
    });

    jQuery('.toggle-manual-invite').on('change', function (e) {

      $value = jQuery(this).val();
      $toggle = jQuery('.show-hide-manual-invite');

      $('#full_name, #volunteer_email_address').val('');

      if( $value === 'not-registered' ){
        $toggle.show();
        $('#full_name').focus();
      } else {
        $toggle.hide();
      }

    });

    jQuery('.js-remove').on('click', removeUser);
    jQuery(document).on('click', '[data-toggle="lightbox"]', function (event) {
      event.preventDefault();
      jQuery(this).ekkoLightbox();
    });

    jQuery('.reset').on('click', resetForm);

    loadDropzones();

    if (window.location.hash === '#change-password' && jQuery('#list-account').length > 0) {
      jQuery('#list-account-list').tab('show');
    }


    // jQuery('#collapseFilter').on('show.bs.collapse', function () {
    //   jQuery('html').addClass('overflow-hidden');
    // });
    //
    // jQuery('#collapseFilter').on('hidden.bs.collapse', function () {
    //   jQuery('html').removeClass('overflow-hidden');
    // });

  })

  jQuery(document).ready(function () {
    groupsMap();
    gdprCookieNotice({
      locale: 'en',
      timeout: 500, //Time until the cookie bar appears
      expiration: 30, //This is the default value, in days
      domain: restarters.cookie_domain, //If you run the same cookie notice on all subdomains, define the main domain starting with a .
      implicit: false, //Accept cookies on page scroll automatically
      statement: '/about/cookie-policy', //Link to your cookie statement page
      performace: ['DYNSRV'], //Cookies in the performance category.
      analytics: ['_ga','_gat', '_gid'], //Cookies in the analytics category.
      marketing: [] //Cookies in the marketing category.
    });
  });

  $('#register-form-submit').on('click', function(e) {
    e.preventDefault();

    if ( $('#consent_gdpr')["0"].checked && $('#consent_future_data')["0"].checked ) {
      $('#register-form').submit();
    } else {
      alert('You must consent to the use of your data in order to register');
    }

  });


  // On toggling between multi collapable invite modal content
  // Then also toggle the link to change the text (show a different link -
  // that has the same functionality)
  $('.multi-collapse-invite-modal').on('show.bs.collapse', function () {
      $('.toggle-modal-link').toggleClass('d-none');
  })

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

  $( document ).ready(function() {

    $(function () {
      $('.btn-calendar-feed').popover({
        html: true,
        title: '',
        trigger: 'click',
        placement: 'bottom',
        sanitize: false,
        delay: { "show": 0, "hide": 0 },
        template: '<div class="popover popover-calendar-feed" role="tooltip"><div class="arrow"></div><div class="popover-body"></div></div>',
        content: $('#calendar-feed').html()
      });
    });

    // Dismissable Alert copy link action
    $('.btn-action').on('click', function () {
      $copy_link = $(this).attr('data-copy-link');
      copyLink($copy_link);
    });

    // Copy Calendar Feed link
    $('#btn-copy').on('click', function () {
      $link = $(this).parents('div').parents('div').find('input[type=text]');
      copyLink($link.val());
    });

    // User Profile Settings - Calendar copy links
    $('.btn-copy-input-text').on('click', function () {
      $link = $(this).parent('div').parent('div').find('input[type=text]');
      copyLink($link.val());
    });

    function copyLink($copy_link) {
      var $temp = $("<input>");
      $("body").append($temp);
      $temp.val($copy_link).select();

      document.execCommand("copy");
      $temp.remove();

      alert("Copied the link: " + $copy_link);
    }


    $('.information-alert').on('closed.bs.alert', function () {
      $dismissable_id = $(this).attr('id');
      $.ajax({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        type: 'POST',
        url: '/set-cookie',
        datatype: 'json',
        data: {
          'dismissable_id': $dismissable_id,
        },
        success: function(data) {
          console.log(true);
        }
      });
    });

    $('.tokenfield').tokenfield();

    $current_column = $('input[name=sort_column]:checked').val();

    $('input[name=sort_column]').on('click', function(e) {
        $form = $('#device-search');
        $sort_direction = $form.find('input[name=sort_direction]');
            if( $sort_direction.val() === 'DSC' ){
                $sort_direction.val('ASC');
            } else {
                $sort_direction.val('DSC');
            }
        $form.submit();
    });

    $('.filter-columns').on('click', function(e) {

      $table = $('#sort-table');

      var hide_columns = $table.find('.'+$(this).data('id'));
      $(hide_columns).toggle();

      var preferences = [];
      $.each($('.filter-columns:checked'), function() {
         preferences.push($(this).val());
      });

      $('.device-colspan').attr('colspan', preferences.length + 3);

      $.ajax({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        type: 'post',
        url: '/device/column-preferences',
        data: {
          column_preferences: preferences,
        }
      });

    });

    $("#invites_to_volunteers").on("click", function(){
      if (this.checked){
        var event_id = $('#event_id').val();

        $.ajax({
          headers: {
            'X-CSRF-TOKEN': $("input[name='_token']").val()
          },
          type: 'get',
          url: '/party/get-group-emails-with-names/'+event_id,
          datatype: 'json',
          success: function(data) {
            var current_items = $('#manual_invite_box').tokenfield('getTokens');
            var new_items = data;

            var pop_arr = [];

            // Keep all the items that were already there.
            current_items.forEach(function(current_item) {
                var manual_email = {
                    value: current_item.value,
                    label: current_item.value
                };
                pop_arr.push(manual_email);
            });

            // Add the new items - i.e. existing volunteers for the group.
            new_items.forEach(function(new_item) {
                var label = '';
                if (! new_item.invites)
                    label += '\u{26A0} ';
                label += new_item.name;

                var volunteer = {
                    value: new_item.email,
                    label: label
                };
                pop_arr.push(volunteer);
            });

            $('#manual_invite_box').tokenfield('setTokens', pop_arr);
          },
            error: function(xhr, status, error) {
                var err = JSON.parse(xhr.responseText);
                console.log(err);
          }
        });
      }
    });

    $('#start-time').on('change', function() {
      var time = $(this).val().split(':');
      var hours = (parseInt(time[0]) + 3).toString();

      if (hours.length < 2) {
        hours = '0' + hours;
      }

      var mins = time[1];

      $('#end-time').val(hours+':'+mins);

    });

    // Copy to clipboard
    // Grab any text in the attribute 'data-copy' and pass it to the
    // copy function
    // ---------------------------------------------------------------------
    $('.js-copy').click(function() {
      var text = $(this).attr('data-copy');
      var el = $(this);
      copyToClipboard(text, el);
    });

  });

  // COPY TO CLIPBOARD
  // Attempts to use .execCommand('copy') on a created text field
  // Copy command
  // boolen if successful then show message
  // After show append original email again
  // Fallback if browser doesn't support .execCommand('copy')
  // ---------------------------------------------------------------------
  function copyToClipboard(content_to_copy, element) {

    var copyTest = document.queryCommandSupported('copy');

    if (copyTest === true) {

      // Create Textarea and Copy Content
      var copyTextArea = document.createElement("textarea");
      copyTextArea.value = content_to_copy;
      document.body.appendChild(copyTextArea);
      copyTextArea.select();

      $original_popover_text = element.attr('data-content');

      try {
        var successful = document.execCommand('copy');
        var message = successful ? 'Copied!' : 'Whoops, not copied!';
        $set_success_message_in_popover = element.attr('data-content', message);
        $show_popover = element.popover('show');

      } catch (err) {
        console.log('Oops, unable to copy');
      }

      document.body.removeChild(copyTextArea);
      $set_original_popover_message = element.attr('data-content', $original_popover_text);

    } else {
      // Fallback if browser doesn't support .execCommand('copy')
      window.prompt("Copy to clipboard: Ctrl+C or Command+C, Enter", content_to_copy);
    }
  }


  function tokenFieldCheck(){
    setTimeout(function(){
      var count_tokens = document.getElementById("manual_invite_box").value.split(",");
      console.log(count_tokens.length);
      if( $('#manual_invite_box').val() === '' ) {
        $('#event-invite-to button, #invite-to-group button').prop('disabled', true);
      } else if( count_tokens.length === 0 ){
        $('#event-invite-to button, #invite-to-group button').prop('disabled', true);
      } else {
        $('#event-invite-to button, #invite-to-group button').prop('disabled', false);
      }
    }, 500);
  }


  $('#manual_invite_box').on('tokenfield:createtoken', function (event) {
    var existingTokens = $(this).tokenfield('getTokens');
    $.each(existingTokens, function(index, token) {
      if (token.value === event.attrs.value)
      event.preventDefault();
    });
    tokenFieldCheck();
  });

  $('#manual_invite_box').on('tokenfield:removedtoken', function (event) {
    tokenFieldCheck();
  });

  $( document ).ready(function() {

    $("textarea#message_to_restarters[name=message_to_restarters]").on("keydown", function(event){
      if (event.which == 13) {
        event.preventDefault();
        this.value = this.value + "\n";
      }
    });
    
    $('#participants_qty').on('change', function() {
      updateParticipants();
    });

    $('#volunteer_qty').on('change', function() {

      var value = parseInt(jQuery(this).parent().find('input[type="number"]').val());
      updateVolunteers();

    });

    $('.add-device').on('submit', function(e) {

      e.preventDefault();
      $form = $(this);

      if( $form.find('select[name=category]').val() === '' ) {
        alert('Category field is required');
        return false;
      }

      $.ajax({
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        },
        type: 'post',
        url: '/device/create',
        data: {
          category: $form.find('select[name=category]').val(),
          weight: $form.find('input[name=weight]').val(),
          brand: $form.find('select[name=brand]').val(),
          model: $form.find('input[name=model]').val(),
          age: $form.find('input[name=age]').val(),
          problem: $form.find('input[name=problem]').val(),
          repair_status: $form.find('select[name=repair_status]').val(),
          repair_details: $form.find('select[name=repair_details]').val(),
          spare_parts: $form.find('select[name=spare_parts]').val(),
          quantity: $form.find('select[name=quantity]').val(),
          event_id: $form.find('input[name=event_id]').val(),
          barrier: $form.find('#repair_barrier').val()
        },
        datatype: 'json',
        success: function(json) {
          if( json.success ){

            //Reset appearance
            $form.trigger("reset");
            jQuery('#device-start').focus();

            $form.find(".select2.select2-hidden-accessible").select2('data', {}); // clear out values selected
            $form.find(".select2.select2-hidden-accessible").select2({ allowClear: false }); // re-init to show default stat

            $form.find(".select2-with-input.select2-hidden-accessible").select2('data', {}); // clear out values selected
            $form.find(".select2-with-input.select2-hidden-accessible").select2(tag_options); // re-init to show default stat

            $form.find('.display-weight').addClass('d-none');
            $form.find('.repair-more').removeClass('col-device-auto');
            $form.find('.repair-details-edit, .spare-parts, .repair-barrier').parents('.col-device').addClass('d-none');
            $form.find('.repair-details-edit, .spare-parts, .repair-barrier').parents('.col-device').removeClass('col-device-auto');
            //EO reset appearance

            //Appending...
            for (i = 0; i < $(json.html).length; i++) {
              var row = $(json.html)[i];
              $target = $(row).hide().appendTo('#device-table > tbody:last-child').fadeIn(1000);
              select2Fields($target);
            }
            $('.table-row-details').removeAttr('style');
            //Finished appending

            //Update stats
            $('#waste-insert').html( json.stats['ewaste'] );
            $('#co2-insert').html(  json.stats['co2'] );
            $('#fixed-insert').html(  json.stats['fixed_devices'] );
            $('#repair-insert').html(  json.stats['repairable_devices'] );
            $('#dead-insert').html(  json.stats['dead_devices'] );

            //Give users some visual feedback
            $('.btn-add').addClass('btn-success');
            $('.btn-add').removeClass('btn-primary');
            setTimeout(function(e){
              $('.btn-add').removeClass('btn-success');
              $('.btn-add').addClass('btn-primary');
            }, 1000);

            loadDropzones();
            $(".select2-with-input").select2("destroy"); //TODO
            $(".select2-with-input").select2(tag_options_with_input); //TODO

          } else if( json ) {

            var error_message = '';
            var error_count = 0;
            $.each( json, function( key, value) {
              if( error_count > 0 ){
                error_message += ', ' + value;
              } else {
                error_message += value;
              }
              error_count++;
            });

            alert(error_message);

          } else {

            alert('Something went wrong, please try again');

          }

          console.log(json);

        },
        error: function(json) {

          if( json.responseJSON.message ){

            alert(json.responseJSON.message);

          } else {

            alert('Something went wrong, please try again');

          }

        }
      });

    });

    jQuery(document).on('submit', '.edit-device', function (e) {

      e.preventDefault();

      var form = $(this);
      var device_id = form.data('device');
      var summary_row = $('#summary-'+device_id);

      if( $('#wiki-'+device_id).is(':checked') ){
        $wiki = 1;
      } else {
        $wiki = 0;
      }

      $category = $('#category-'+device_id).val();
      $category_name = $('#category-'+device_id+' option:selected').text();
      $weight = $('#weight-'+device_id).val();
      $brand = $('#brand-'+device_id).val();
      $model = $('#model-'+device_id).val();
      $age = $('#age-'+device_id).val();
      $problem = $('#problem-'+device_id).val();
      $repair_status = parseInt($('#status-'+device_id).val());
      $repair_details = parseInt($('#repair-info-'+device_id).val());
      // $repair_details_name = $('#repair-info-'+device_id+' option:selected').text();
      $spare_parts = parseInt($('#spare-parts-'+device_id).val());
      $barrier = $('#barrier-'+device_id).val();
      $event_id = $('#event_id').val();

      //Visual improvements
      $(this).find(':input').attr("disabled", true);
      $('.btn-save2').text('Saving...');

      $.ajax({
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        },
        type: 'post',
        url: '/device/edit/'+device_id,
        data: {
          category: $category,
          weight: $weight,
          brand: $brand,
          model: $model,
          age: $age,
          problem: $problem,
          repair_status: $repair_status,
          repair_details: $repair_details,
          spare_parts: $spare_parts,
          wiki: $wiki,
          event_id: $event_id,
          barrier: $barrier,
          // files:$('#file-'+device_id).val(),
        },
        datatype: 'json',
        success: function(data) {

          $('#waste-insert').html( data.stats.ewaste );
          $('#co2-insert').html(  data.stats.co2 );
          $('#fixed-insert').html(  data.stats.fixed_devices );
          $('#repair-insert').html(  data.stats.repairable_devices );
          $('#dead-insert').html(  data.stats.dead_devices );

          if (data.error) {
            alert(data.error);
            // } else if (data.success) {
            //   alert(data.success);
          }

          //Visual improvements
          setTimeout(function(e){
            form.find(':input').attr("disabled", false);
            $('.btn-save2').addClass('btn-success').removeClass('btn-primary').text('Saved');
          }, 1000);

          //Visual improvements
          setTimeout(function(e){
            $('.btn-save2').removeClass('btn-success').addClass('btn-primary').text('Update');
          }, 3000);

          // Reset if none of the above is selected
          if( $category_name === 'None of the above' )
            $category_name = 'Misc';

          summary_row.find('.category').text($category_name);
          summary_row.find('.brand').text($brand);
          summary_row.find('.model').text($model);
          summary_row.find('.age').text($age);
          summary_row.find('.problem').text($problem);

          if( $repair_status === 1 ){
            summary_row.find('.repair_status').empty().html('<span class="badge badge-success">Fixed</span>');
          } else if( $repair_status === 2 ){
            summary_row.find('.repair_status').empty().html('<span class="badge badge-warning">Repairable</span>');
          } else if( $repair_status === 3 ){
            summary_row.find('.repair_status').empty().html('<span class="badge badge-danger">End</span>');
          } else {
            summary_row.find('.repair_status').empty();
          }

          // if( $repair_details === 0 ){
          //   summary_row.find('.repair_details').text('N/A');
          // } else {
          //   summary_row.find('.repair_details').text($repair_details_name);
          // }

          // Hide tick when no spare parts selected or not needed
          if( $spare_parts == 0 || $spare_parts == 2 ){
            summary_row.find('.table-tick').hide();
          } else {
            summary_row.find('.table-tick').show();
          }

        },
        error: function(error) {
          alert(error);
        }
      });

    });

    jQuery(document).on('click', '.delete-device', function (e) {

      e.preventDefault();
      if (window.confirm("Are you sure? This cannot be undone.")) {
        $device = jQuery(this).data('device-id');
        $href = $(this).attr('href');
        $.ajax({
          type: 'get',
          url: $href,
          success: function(data) {
            $('#summary-'+$device).fadeOut(1000);
            $('#row-'+$device).fadeOut(1000);
          },
          error: function(error) {
            alert(error);
          }
        });
      }

    });

    $('.ajax-delete-image').on('click', function (e) {
      e.preventDefault();

      if (window.confirm("Are you sure? This cannot be undone.")) {
        $this = jQuery(this);
        $device = jQuery(this).data('device-id');
        $href = $(this).attr('href');
        $.ajax({
          type: 'get',
          url: $href,
          success: function(data) {
            $this.parent().fadeOut(1000);
          },
          error: function(error) {
            alert(error);
          }
        });
      }
    });

    $('#description').on('summernote.change', function(e) {
      $('#free_text').val($('#description').summernote('code'));
    });

    $("#registeremail").blur(function() {

      if ( $(this).val().length > 0 ){

        var email = $('#registeremail').val();

        $.ajax({
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          type:'POST',
          url:'/user/register/check-valid-email',
          data: {
            email : email
          },
          dataType : 'json',
          success: function(response){
            $('div.emailtest > .invalid-feedback').text(response['message']).show();
          },
          error: function(){
            $('.invalid-feedback').hide();
          }
        });

      }
    });

    // If event has attended or invited people then user cannot delete the event
    $("#deleteEvent").click(function (e) {
      if($(this).attr('data-count-attended') > 0 || $(this).attr('data-count-invited') > 0 || $(this).attr('data-count-volunteers') > 0) {
        return confirm('Are you sure you want to delete this event?');

        id = $(this).attr('data-party-id');

        $.ajax({
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          type:'POST',
          url:'/party/delete/'+id,
          data: {
            "id" : id,
          },
          dataType : 'json',
        });

      }
    });

    // Set min height so the language menu sits just under the overall height of the browser window
    $('body > .container').css('min-height', ( $(window).height() - $('nav.navbar').height() ) +'px');

    $(".toggle-invite-modals").click(function (e) {

      $('#invite-to-group').modal('toggle');
      $('#event-invite-to').modal('toggle');

      $('#shareable-modal').modal('toggle');
    });

    $('.select2-with-input-group').on("select2:select", function(e) {
      $input_field = $(this).parents('.input-group-select2').find('input[type=text]');

      $current_url = $input_field.val();

      $remove_current_area = $current_url.lastIndexOf('/') + 1;
      $creating_new_url =  $current_url.substring( 0, $remove_current_area );
      $new_url = $creating_new_url.concat( $(this).val() );

      $input_field.val($new_url);
    });

  });

  // Copy Calendar Feed link
  $(document).on("click", "#btn-copy", function () {
    $copy_link = $(this).parents('div').parents('div').find('input[type=text]').val();

    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val($copy_link).select();

    document.execCommand("copy");
    $temp.remove();

    alert("Copied the link: " + $copy_link);
  });
