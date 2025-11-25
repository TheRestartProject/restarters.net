/**
* First we will load all of this project's JavaScript dependencies which
* includes Vue and other libraries. It is a great starting point when
* building robust, powerful web applications using Vue and Laravel.
*/

// Import vendor dependencies first to ensure jQuery and plugins are available
import './vendor';

import './bootstrap';

// Import all other dependencies first
import './misc/notifications';
import 'leaflet';
import './constants';

import Vue from 'vue'
import { BootstrapVue, IconsPlugin } from 'bootstrap-vue'
import store from './store'
// Leaflet components moved to individual Vue components that use them
import Multiselect from 'vue-multiselect'
import 'vue-multiselect/dist/vue-multiselect.min.css'
import * as Sentry from '@sentry/vue';

// Import only existing Vue components
import DashboardPage from './components/DashboardPage.vue'
import EventAddEditPage from './components/EventAddEditPage.vue'
import EventAddEdit from './components/EventAddEdit.vue'
import EventsRequiringModeration from './components/EventsRequiringModeration.vue'
import EventPage from './components/EventPage.vue'
import FixometerPage from './components/FixometerPage.vue'
import GroupsPage from './components/GroupsPage.vue'
import GroupPage from './components/GroupPage.vue'
import GroupAddEditPage from './components/GroupAddEditPage.vue'
import GroupEventsPage from './components/GroupEventsPage.vue'
import GroupEvents from './components/GroupEvents.vue'
import GroupsRequiringModeration from './components/GroupsRequiringModeration.vue'
import EventTimeRangePicker from './components/EventTimeRangePicker.vue'
import EventDatePicker from './components/EventDatePicker.vue'
import VenueAddress from './components/VenueAddress.vue'
import RichTextEditor from './components/RichTextEditor.vue'
import Notifications from './components/Notifications.vue'
import GroupTimeZone from './components/GroupTimeZone.vue'
import StatsShare from './components/StatsShare.vue'
import CategoriesTable from './components/CategoriesTable.vue'
import RolesTable from './components/RolesTable.vue'

import lang from './mixins/lang'

import Vuelidate from 'vuelidate'

import LoginPage from './components/LoginPage.vue'

import LangMixin from './mixins/lang'
import { Lang } from './mixins/lang'


import Dropzone from 'dropzone'

import Icon from 'vue-awesome/components/Icon'
import 'vue-awesome/icons/sync'
import 'vue-awesome/icons/save'
import 'vue-awesome/icons/check'

// Leaflet components will be loaded dynamically to avoid ES module issues

// Vue configuration
Vue.use(BootstrapVue)
Vue.use(IconsPlugin)
Vue.use(Vuelidate)
Vue.component('v-icon', Icon)
Vue.component('multiselect', Multiselect)
Vue.mixin(lang)

// Wait for jQuery to be available, then run all jQuery-dependent code
// TODO: This entire jQuery initialization block is temporary and will be removed
// as we gradually migrate from Blade templates to Vue components. Each piece of
// jQuery functionality should be replaced with Vue component equivalents.
function initializeJQuery() {
  if (typeof window === 'undefined' || !window.jQuery) {
    setTimeout(initializeJQuery, 50);
    return;
  }

  // Wait for Leaflet to be available
  // Note: Bootstrap loads from CDN and may not be ready yet, but that's OK
  if (typeof window.L === 'undefined') {
    setTimeout(initializeJQuery, 50);
    return;
  }
  
  const $ = window.jQuery;
  
  // All jQuery initialization code goes here
  $('.btn-next').on('click', formProcess);
  $('.registration__prev').on('click', formProcessPrev);
  
  // Document ready functionality
  $(document).ready(function() {
    try {
      // Continue with all other jQuery code that was in the file
      if ($('section.registration').length > 0 && $('.alert.alert-danger').length > 0 && $('.is-invalid').length > 0) {
        $('.registration__step').removeClass('registration__step--active');
        $('.is-invalid').first().parents('.registration__step').addClass('registration__step--active');
      }

      // Initialize all other jQuery-dependent functionality here
      registration();
      onboarding();
      eventsMap();
      truncate();
      nestedTable();
      // Initialize popover functionality with retry mechanism
      setTimeout(function () {
        if (typeof window.jQuery.fn.popover !== 'undefined') {
          $('.users-list').find('[data-toggle="popover"]').popover();
          $('.users-list').find('[data-toggle="popover"]').on('click', function (e) {
            $('.users-list').find('[data-toggle="popover"]').not(this).popover('hide');
          });

          $('.table:not(.table-devices)').find('[data-toggle="popover"]').popover({
            template: '<div class="popover popover__table" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>',
            placement: 'top'
          });

          $('.table-devices').find('[data-toggle="popover"]').popover();

          $('.table').find('[data-toggle="popover"]').on('click', function (e) {
            $('.table').find('[data-toggle="popover"]').not(this).popover('hide');
          });
        } else {
          console.warn('Bootstrap popover still not available after delay');
        }
      }, 100);

      $(document).on('change', '.category', function (e) {
        var $value = parseInt($(this).val());
        var $field = $(this).parents('td').find('.weight');

        if (!$field.length) {
          // At present this global JS is used in both old and new designs which have different DOM structure, so we
          // need to cope with both.
          $field = $(this).parents('.card-body').find('.weight')
        }
        if ($value === 46 || $value === '') {
          $field.prop('disabled', false);
          $field.parents('.display-weight').removeClass('d-none');
        } else {
          $field.val('');
          $field.trigger('change');
          $field.prop('disabled', true);
          $field.parents('.display-weight').addClass('d-none');
        }
      });

      function removeUser () {

        var id = $(this).data('remove-volunteer');

        $.ajax({
          headers: {
            'X-CSRF-TOKEN': $("input[name='_token']").val()
          },
          type: 'post',
          url: '/party/remove-volunteer',
          data: {
            id: id,
          },
          datatype: 'json',
          success: function (json) {
            if (json.success) {
              $('.volunteer-' + id).fadeOut();
            } else {
              alert('Something has gone wrong');
            }
          },
          error: function (error) {
            alert('Something has gone wrong');
          }
        });

      }

      $('.js-remove').on('click', removeUser);
      $(document).on('click', '[data-toggle="lightbox"]', function (event) {
        event.preventDefault();
        $(this).ekkoLightbox();
      });

      $('.reset').on('click', resetForm);

      loadDropzones();

      if (window.location.hash === '#change-password' && $('#list-account').length > 0) {
        $('#list-account-list').tab('show');
      }

      // Make navbar hide on mobile scroll.
      var showNavbar = true;
      var lastScrollPosition = true;

      window.addEventListener('scroll', function () {
        // Get the current scroll position
        const currentScrollPosition = window.pageYOffset || document.documentElement.scrollTop

        // Because of momentum scrolling on mobiles, we shouldn't continue if it is less than zero
        if (currentScrollPosition < 0) {
          return
        }

        // Here we determine whether we need to show or hide the navbar
        showNavbar = currentScrollPosition < lastScrollPosition

        if (showNavbar) {
          $('#nav-left').removeClass('nav-left--hidden')
        } else {
          $('#nav-left').addClass('nav-left--hidden')
        }

        // Set the current scroll position as the last scroll position
        lastScrollPosition = currentScrollPosition
      })

      // Initialize Sentry
      // Sentry error
      Sentry.init({
        Vue,
        dsn: "https://50fd2fa440af4bb4a230f40ca8d8cf90@o879179.ingest.sentry.io/5831645",
        integrations: [
          Sentry.browserTracingIntegration(),
        ],

        // We are low traffic, so we can capture all performance events.
        tracesSampleRate: 1.0,
        ignoreErrors: [
          'ResizeObserver loop limit exceeded',
          // Random plugins/extensions
          'top.GLOBALS',
          // See: http://blog.errorception.com/2012/03/tale-of-unfindable-js-error.html
          'originalCreateNotification',
          'canvas.contentDocument',
          'MyApp_RemoveAllHighlights',
          'http://tt.epicplay.com',
          'Can\'t find variable: ZiteReader',
          'jigsaw is not defined',
          'ComboSearch is not defined',
          'http://loading.retry.widdit.com/',
          'atomicFindClose',
          // Facebook borked
          'fb_xd_fragment',
          // ISP "optimizing" proxy - `Cache-Control: no-transform` seems to
          // reduce this. (thanks @acdha)
          // See http://stackoverflow.com/questions/4113268
          'bmi_SafeAddOnload',
          'EBCallBackMessageReceived',
          // See http://toolbar.conduit.com/Developer/HtmlAndGadget/Methods/JSInjection.aspx
          'conduitPage'
        ],
        beforeSend: function beforeSend (event) {
          if (window.restarters.analyticsCookieEnabled) {
            return event;
          } else {
            return null;
          }
        }
      });
      // Sentry initialized

      /**
       * Vue instances are initialized outside of jQuery.ready for each .vue element
       * See the initialization code after the jQuery.ready block
       */

      // jQuery initialization complete - Vue will be initialized next
      $(".vue-placeholder-large").hide()

      $('.btn-calendar-feed').popover({
        trigger: 'focus'
      });

      $('.btn-action').on('click', function () {
        setTimeout(copyLinkUser, 200);
      });

      $('#btn-copy').on('click', function () {
        copyLinkUser();
      });

      $('.btn-copy-input-text').on('click', function () {
        copyLinkUser();
      });

      $('.information-alert').on('closed.bs.alert', function () {
        localStorage.setItem('information-alert-closed', 'true');
      });

      // Enable submit button when email textarea has content
      $('#manual_invite_box').on('input', function() {
        var hasContent = $(this).val().trim().length > 0;
        $('#event-invite-to button[type="submit"], #invite-to-group button[type="submit"]').prop('disabled', !hasContent);
      });

      $(document).on("click", "#btn-copy", function () {
        var $copy_link = $(this).data('clipboard-text');
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($copy_link).select();
        document.execCommand("copy");
        $temp.remove();

        alert("Copied the link: " + $copy_link);
      });

      // Hash-based tab switching
      let hash = document.location.hash;
      if (hash) {
        $('a[href=\"' + hash).tab('show');
      }

      // Enable popovers - Bootstrap doesn't enable these by default.
      $('[data-toggle="popover"]').popover();

      // Handle invite modal toggle links - swap visibility when clicked
      $('.toggle-modal-link').on('click', function(e) {
        // Toggle d-none class on both toggle links
        $('.toggle-modal-link').toggleClass('d-none');
      });

      // All other jQuery initialization continues here...
      groupsMap();

      if (window.gdprCookieNotice && !window.noCookieNotice) {
        gdprCookieNotice({
          locale: 'en',
          timeout: 500,
          expiration: 30,
          domain: restarters.cookie_domain,
          implicit: false,
          statement: '/about/cookie-policy',
          performace: ['DYNSRV'],
          analytics: ['_ga', '_gat', '_gid'],
          marketing: []
        });
      }

      // Information alerts
      $('.information-alert').on('closed.bs.alert', function () {
        var $dismissable_id = $(this).attr('id');
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
          success: function (data) {
            console.log(true);
          }
        });
      });

      // Copy Calendar Feed link
      $(document).on("click", "#btn-copy", function () {
        var $copy_link = $(this).parents('div').parents('div').find('input[type=text]').val();

        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($copy_link).select();

        document.execCommand("copy");
        $temp.remove();

        alert("Copied the link: " + $copy_link);
      });


      // Initialize Vue instances on any divs which have asked for it.
      //
      // Normally you'd initialise one instance on a single top-level div.  But we put content directly under body.
      // Initialising multiple instances is a bit more expensive, but not much.
      //
      // We need to list all the top-level components we will use in blades here; they are stored in
      // resources/js/components.  Lower level components can be included from within those as normal;
      // they don't need listing here.
      console.log('About to initialize Vue instances, found', window.jQuery(".vue").length, 'elements');
      window.jQuery(".vue").each(function(index) {
        console.log('Initializing Vue instance', index);
        new Vue({
          el: window.jQuery(this).get(0),
          store: store,
          components: {
            'loginpage': LoginPage,
            'dashboardpage': DashboardPage,
            'eventaddeditpage': EventAddEditPage,
            'eventaddedit': EventAddEdit,
            'eventsrequiringmoderation': EventsRequiringModeration,
            'eventpage': EventPage,
            'fixometerpage': FixometerPage,
            'groupspage': GroupsPage,
            'grouppage': GroupPage,
            'groupaddeditpage': GroupAddEditPage,
            'groupeventspage': GroupEventsPage,
            'groupevents': GroupEvents,
            'groupsrequiringmoderation': GroupsRequiringModeration,

            'eventtimerangepicker': EventTimeRangePicker,
            'eventdatepicker': EventDatePicker,
            'venueaddress': VenueAddress,
            'richtexteditor': RichTextEditor,
            'notifications': Notifications,
            'grouptimezone': GroupTimeZone,
            'statsshare': StatsShare,
            'categoriestable': CategoriesTable,
            'rolestable': RolesTable,
          }
        })
      })

      // Initialize Leaflet components now that CDN Leaflet is available
      try {
        // Use vue2-leaflet with the global Leaflet from CDN
        import('vue2-leaflet').then((leafletModule) => {
          Vue.component('l-map', leafletModule.LMap);
          Vue.component('l-marker', leafletModule.LMarker);
          Vue.component('l-tile-layer', leafletModule.LTileLayer);
        }).catch((e) => {
          console.warn('Vue2-Leaflet components not available, using fallback:', e.message);
        });
      } catch (e) {
        console.warn('Vue2-Leaflet components not available:', e.message);
      }

      // All remaining jQuery initialization code goes here
      // Global JS initialization complete
    } catch (e) {
      console.error('Global JS initialization error:', e);
    }
  });
}

// Start the initialization
initializeJQuery();

// Create a jQuery reference for use in functions (will be available after initialization)
let jQuery;



// Leaflet icon setup moved to components that use Leaflet



window.Dropzone = Dropzone;
// Note: slick-carousel is loaded via script tag in HTML template due to parsing issues

// Slick carousel initialization moved to footer after CDN script loads

function validateForm() {

  var form = window.jQuery('#step-2');
  var validCount = 0;

  var validation = Array.prototype.filter.call(form, function (form) {

    // Convert to Array, as IE doesn't support forEach on NodeList.
    // See e.g. https://github.com/babel/babel/issues/6511#issuecomment-338076009
    var requiredFields = form.querySelectorAll('[required]');
    var requiredFieldsArray = Array.from(requiredFields);
    requiredFieldsArray.forEach(element => {

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

    var valid = validCount === window.jQuery('#step-2').find('input,select').filter('[required]:visible').length

    if ( window.jQuery('#password').length > 0 && window.jQuery('#password').val().length < 6 ) {

      window.jQuery('#password').addClass('is-invalid');
      window.jQuery('#password-confirm').addClass('is-invalid');
      window.jQuery('.email-invalid-feedback').show();
      valid = false;

    } else if ( window.jQuery('#password').length > 0 && window.jQuery('#password').val() !== window.jQuery('#password-confirm').val() ) {

      window.jQuery('#password').addClass('is-invalid');
      window.jQuery('#password-confirm').addClass('is-invalid');
      window.jQuery('.email-invalid-feedback').show();
      valid = false;

    } else {

      window.jQuery('#password').removeClass('is-invalid');
      window.jQuery('#password-confirm').removeClass('is-invalid');
      window.jQuery('.email-invalid-feedback').hide();

      window.jQuery('.registration__step').removeClass('registration__step--active');
      window.jQuery('#step-3').addClass('registration__step--active');

    }

    return valid

  });




}

function formProcess(e) {
  var target = window.jQuery(this).data('target');
  var targetLabel = document.getElementById(target+'-form-label');
  e.preventDefault();

  window.jQuery('.btn-next').attr('aria-expanded', 'false');
  window.jQuery(this).attr('aria-expanded', 'true');
  if ( window.jQuery('#step-2.registration__step--active').length > 0 ) {
    validateForm();
  } else {
    window.jQuery('.registration__step').removeClass('registration__step--active');
    window.jQuery('#' + target).addClass('registration__step--active');
    if (targetLabel) { targetLabel.scrollIntoView(500) }
  }

}

function formProcessPrev(e) {
  var target = window.jQuery(this).data('target');
  var targetLabel = document.getElementById(target+'-form-label');
  e.preventDefault();

  window.jQuery('.registration__step').removeClass('registration__step--active');
  window.jQuery('.btn-next').attr('aria-expanded','false');
  window.jQuery('#' + target).addClass('registration__step--active');
  window.jQuery(this).attr('aria-expanded', 'true');

  if (targetLabel) { console.log(targetLabel); targetLabel.scrollIntoView(500) }

}

// jQuery event bindings moved to initializeJQuery() function at top of file

function registration() {

  if ( window.jQuery('section.registration').length > 0 && window.jQuery('.alert.alert-danger').length > 0 && window.jQuery('.is-invalid').length > 0 ) {

    window.jQuery('.registration__step').removeClass('registration__step--active');
    window.jQuery('.is-invalid').first().parents('.registration__step').addClass('registration__step--active');

  }

}

function onboarding() {
  if ( window.jQuery('body.onboarding').length > 0 ) {

    window.jQuery('#onboarding').modal('show');

    window.jQuery('#onboarding').on('shown.bs.modal', function () {
      window.jQuery('.modal-slideshow').slick({
        dots: true, arrows: true, infinite: false,
        prevArrow: '.modal-prev',
        nextArrow: '.modal-next'
      });
    });

    //if ( window.jQuery('.slick-initialized').length > 0 ) {

    window.jQuery('#onboarding').on('beforeChange', function (event, slick, currentSlide, nextSlide) {
      if (nextSlide === 2) {
        window.jQuery('.modal-finished').addClass('modal-finished--visible');
        window.jQuery('.close').addClass('close--visible');
      } else {
        window.jQuery('.modal-finished').removeClass('modal-finished--visible');
        window.jQuery('.close').removeClass('close--visible');
      }
    });

    //}


    window.jQuery('#onboarding').on('hide.bs.modal', function (e) {
      $.ajax({
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        },
        type: 'get',
        url: '/user/onboarding-complete',
      });
      window.jQuery('.modal-slideshow').slick('destroy');
    });

  }
}

// serialize function removed - tokenfield has been replaced with textarea

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

        if (addressType === 'postal_code') {
          // We have a postcode field which is editable.  If we have no existing postcode and we've just geocoded, then
          // set up any postcode we have returned as a sensible default.
          var postcode = document.getElementById('postcode')
          if (val && !postcode.value) {
            postcode.value = val;
          }
        }
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
    if (window.jQuery('.field-geolocate').length > 0 ) {
      initAutocomplete();
    }
  }

  function truncate() {

    if (window.jQuery('.truncate').length > 0) {
      window.jQuery('.truncate').each(function () {
        window.jQuery(this).parent().addClass('truncated');
      });
    }

    var button = window.jQuery('.truncate__button');
    button.on('click', function (e) {
      e.preventDefault();

      if (window.jQuery(this).parent().hasClass('truncated')) {
        window.jQuery(this).parent().removeClass('truncated');
        window.jQuery(this).find('span').text('Show less');
      } else {
        window.jQuery(this).parent().addClass('truncated');
        window.jQuery(this).find('span').text('Read more');
      }
    });
  }

  function eventsMap() {
    if ( window.jQuery('#event-map').length > 0 ) {

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

  function nestedTable() {

    window.jQuery('.table-row-details').each(function(){

      window.jQuery(this).on('show.bs.collapse', function () {
        window.jQuery(this).prev('tr').addClass('active-row');
        //window.jQuery(this).prev('tr').find('.row-button')
      })
      window.jQuery(this).on('hide.bs.collapse', function () {
        window.jQuery(this).prev('tr').removeClass('active-row');
      })

    });


  }

  function loadDropzones() {
    if (window.jQuery(".dropzoneEl").length > 0 ) {
      var field1 = window.jQuery('.dropzone').data('field1');
      var field2 = window.jQuery('.dropzone').data('field2');

      var preview = ".uploads";
      var dropzone_id = ".dropzoneEl";
      var prefix = '';

      $(".dropzoneEl").each(function( index ) {

        var $dropzone = $(this);

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

    if (window.jQuery("#dropzoneSingleEl").length > 0) {

      var field1 = window.jQuery('#dropzoneSingleEl').data('field1');
      var field2 = window.jQuery('#dropzoneSingleEl').data('field2');

      var instanceDropzone = new Dropzone("#dropzoneSingleEl", {
        init: function () {
          window.jQuery(".dz-message").find('span').text(field1);
          window.jQuery(".dz-message").append('<small>'+field2+'</small>');
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

    if (window.jQuery("#dropzoneSingleEl-create").length > 0) {

      var field1 = window.jQuery('#dropzoneSingleEl-create').data('field1');
      var field2 = window.jQuery('#dropzoneSingleEl-create').data('field2');

      var instanceDropzone = new Dropzone("#dropzoneSingleEl-create", {
        init: function () {
          window.jQuery(".dz-message").find('span').text(field1);
          window.jQuery(".dz-message").append('<small>'+field2+'</small>');
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
    var attr = window.jQuery(this).data('form');
    var form = window.jQuery('#' + attr);
    form[0].reset();

    if (form.find('select').length > 0 ) {
      form.find('select').val('').trigger('change');
    }

  }

  var tag_options = {
    tags: true,
    createTag: function (params) {
      return null;
    }
  }

  var repair_barrier_options = {
    placeholder: "Choose barriers to repair"
  }

  var tag_options_with_input = {
    tags: true,
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

  // select2Fields function removed - Select2 has been replaced with native selects

  Dropzone.autoDiscover = false;
  // All remaining jQuery initialization code moved to initializeJQuery() function

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

      var $original_popover_text = element.attr('data-content');

      try {
        var successful = document.execCommand('copy');
        var message = successful ? 'Copied!' : 'Whoops, not copied!';
        var $set_success_message_in_popover = element.attr('data-content', message);
        var $show_popover = element.popover('show');

      } catch (err) {
        console.log('Oops, unable to copy');
      }

      document.body.removeChild(copyTextArea);
      var $set_original_popover_message = element.attr('data-content', $original_popover_text);

    } else {
      // Fallback if browser doesn't support .execCommand('copy')
      window.prompt("Copy to clipboard: Ctrl+C or Command+C, Enter", content_to_copy);
    }
  }


  function tokenFieldCheck(){
    setTimeout(function(){
      var count_tokens = document.getElementById("manual_invite_box").value.split(",");
      var disabled = false

      if( $('#manual_invite_box').val() === '' ) {
        disabled = true
      } else if( count_tokens.length === 0 ) {
        disabled = true
      } else {
        for (var i = 0; i < count_tokens.length; i++) {
          if (!count_tokens[i] || count_tokens[i].indexOf('@') == -1 ) {
            disabled = true
          }
        }
      }

      $('#event-invite-to button, #invite-to-group button').prop('disabled', disabled);
    }, 500);
  }



  function deviceFormCollect($form) {
    var formdata = $form.serializeArray()

    // The event id is not held in the form itself.
    formdata.push({
      'name': 'event_id',
      'value': $('#event_id').val()
    })

    // The wiki flag is passed as 0/1 not true/false.
    formdata = formdata.map((v) => {
      if (v.name === 'wiki') {
        return v.value ? 1 : 0
      } else {
        return v
      }
    })

    return formdata
  }

  function deviceFormEnableDisable(form, disabled) {
    form.find(':input').attr("disabled", disabled);
  }

  // Final jQuery block moved to initializeJQuery() function

// All jQuery initialization moved to initializeJQuery() function above
// Sentry initialization is also inside the initializeJQuery() function

// Start jQuery initialization (called earlier on line 509, don't duplicate here)
// initializeJQuery();

