// import jquery from 'jquery';
// window.$ = window.jQuery=jquery;

// window.bootstrap = require('bootstrap');

window.onload = function() {

  (function($, window, document) {
    // Use strict mode to reduce development errors.
    "use strict";

    $(document).ready(function() {
      require('./components/dropdown.js');
      require('./components/ajax-search-discourse-notifications.js');
      require('./components/check-auth.js');

      console.log('Global js ready!');

      // Keep hash within URL when toggling between Bootstrap Panes/Tabs
      $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if(history.pushState) {
          history.pushState(null, null, $(this).attr('href'));
        } else {
          location.hash = $(this).attr('href');
        }
      });

      $("form[id$='-search']").submit(function (e) {
        if ($('#formHash').length) {
          $('#formHash').val(window.location.hash);
        } else {
          $(this).append(
            $('<input>', {
              type: 'hidden',
              id: 'formHash',
              name: 'formHash',
              val: window.location.hash
            })
          );
        }
      });
    });

    // Change Bootstrap Pane/Tab view onload where hash is within URL
    var hash = window.location.hash;

    if ( $('#formHash').length ) {
      var hash = $('#formHash').val();
    }

    if(hash != '' || hash != undefined) {
      var $element = $('a[href="' + hash + '"]');
      if ($element.length == 1) {
        $element.tab('show');
      }
    }

    if (window.location.origin == 'https://test-wiki.rstrt.org') {
      $('.wiki-nav-item').addClass('active');

      $('.nav-tabs-block li a').removeClass('active');

      $('.nav-tabs-block li a[href*="'+ window.location.pathname +'"]').each(function() {
        $(this).addClass('active');
      });
    }
  })(jQuery, window, document);
}
