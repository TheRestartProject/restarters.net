
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./jquery.min');
require('./bootstrap.min');
require('./bootstrap');
require('./bootstrap-tokenfield.min.js');
require('select2');
require('slick-carousel');
require('summernote');
require('ekko-lightbox');
window.Dropzone = require('dropzone');
// window.Tokenfield = require("tokenfield");

// window.Vue = require('vue');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

// Vue.component('example-component', require('./components/ExampleComponent.vue'));
//
// const app = new Vue({
//     el: '#app'
// });

if ( jQuery('.slideshow').length > 0 ) {
    jQuery('.slideshow').slick({
        dots: true, arrows:false
    });
}

function validateForm() {

    var forms = jQuery('#step-2-form');
    var validCount = 0;

    var validation = Array.prototype.filter.call(forms, function (form) {

        form[0].querySelectorAll('[required]').forEach(element => {

            //console.log(element.checkValidity());

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

        if ( validCount !== jQuery('#step-2-form').find('input,select').filter('[required]:visible').length ) {
            return false;

        } else if ( jQuery('#password').val() !== jQuery('#password2').val() ) {

            jQuery('#password').addClass('is-invalid');
            jQuery('#password2').addClass('is-invalid');
            return false;

        } else {

            jQuery('#password').removeClass('is-invalid');
            jQuery('#password2').removeClass('is-invalid');

            jQuery('.registration__step').removeClass('registration__step--active');
            jQuery('#step-3').addClass('registration__step--active');

        }

    });




}

function formProcess(e) {
    var target = jQuery(this).data('target');
    e.preventDefault();

    jQuery('.btn-next').attr('aria-expanded', 'false');
    jQuery(this).attr('aria-expanded', 'true');

    if ( jQuery('.registration__step--active').find('#step-2-form').length > 0 ) {
        validateForm();
    } else {
        jQuery('.registration__step').removeClass('registration__step--active');
        jQuery('#' + target).addClass('registration__step--active');
    }

}

function formProcessPrev(e) {
    var target = jQuery(this).data('target');
    e.preventDefault();

    jQuery('.registration__step').removeClass('registration__step--active');
    jQuery('.btn-next').attr('aria-expanded','false');
    jQuery('#' + target).addClass('registration__step--active');
    jQuery(this).attr('aria-expanded', 'true');

}

jQuery('.btn-next').on('click',formProcess);
jQuery('.registration__prev').on('click', formProcessPrev);

function onboarding() {
    if ( jQuery('.onboarding').length > 0 ) {

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

function initTokenfields() {
    if ( document.querySelectorAll('.tokenfield').length > 0 ) {

        var tokens = document.querySelector('#prepopulate');

        var tf = new Tokenfield({
            el: document.querySelector('.tokenfield')
        });

        tf.on('change', function () {
            var out = JSON.stringify(serialize(tf), null, 2);
            tokens.value = out;
        });

    }

}

function groupsMap() {
    if ( jQuery('#map-plug').length > 0 ) {

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

    const mapObject = document.querySelector('#event-map');

    if (jQuery('#event-map').length > 0) {

        let map;
        let latitude = parseFloat(mapObject.dataset.latitude);
        let longitude = parseFloat(mapObject.dataset.longitude);
        let zoom = parseFloat(mapObject.dataset.zoom);

        map = new google.maps.Map(document.getElementById('event-map'), {
            center: { lat: latitude, lng: longitude },
            zoom: zoom
        });

        let uluru = { lat: latitude, lng: longitude };
        let marker = new google.maps.Marker({ position: uluru, map: map });


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
}

function removeUser() {
    jQuery(this).parent().remove();
    // AJAX remove entry...
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

    if (jQuery("#dropzoneEl").length > 0 ) {

        var field1 = jQuery('#dropzoneEl').data('field1');
        var field2 = jQuery('#dropzoneEl').data('field2');

        var instanceDropzone = new Dropzone("#dropzoneEl", {
            init: function () {
                jQuery(".dz-message").find('span').text(field1);
                jQuery(".dz-message").append('<small>'+field2+'</small>');
            },
            paramName: "file", // The name that will be used to transfer the file
            maxFilesize: 2,
            uploadMultiple: true,
            createImageThumbnails: true,
            thumbnailWidth: 70,
            thumbnailHeight: 60,
            thumbnailMethod: "contain",
            addRemoveLinks: true,
            previewsContainer: ".previews"
        });

    }
}

Dropzone.autoDiscover = false;
onboarding();
// initTokenfields();
groupsMap();
textEditor();
numericInputs();
eventsMap();
truncate();
nestedTable();

jQuery(function () {
    jQuery('.users-list').find('[data-toggle="popover"]').popover();

    jQuery('.users-list').find('[data-toggle="popover"]').on('click', function (e) {
        jQuery('.users-list').find('[data-toggle="popover"]').not(this).popover('hide');
    });

    jQuery('.table').find('[data-toggle="popover"]').popover({
        template: '<div class="popover popover__table" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>',
        placement:'top'
    })

    jQuery('.table').find('[data-toggle="popover"]').on('click', function (e) {
        jQuery('.table').find('[data-toggle="popover"]').not(this).popover('hide');
    });

    jQuery('.form-control > select').select2();
    jQuery('.js-remove').on('click', removeUser);
    jQuery(document).on('click', '[data-toggle="lightbox"]', function (event) {
        event.preventDefault();
        jQuery(this).ekkoLightbox();
    });

    loadDropzones();
})

require('./app_additional');
