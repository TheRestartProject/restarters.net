// jQuery loaded from CDN in header, not bundled

import './components/dropdown.js';
import './components/ajax-search-discourse-notifications.js';
import './components/check-auth.js';

// Use strict mode to reduce development errors.
"use strict";

// Wait for jQuery to be available before executing any jQuery code
function initWhenReady() {
  if (typeof window.jQuery === 'undefined') {
    setTimeout(initWhenReady, 50);
    return;
  }

  // Use global jQuery
  const $ = window.jQuery;

  $(document).ready(function() {
    console.log('GLOBAL/js/app.js ready!');

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

  // TODO: how to get this from .env?
  if (window.location.origin == 'https://wiki.restarters.dev' || window.location.origin == 'https://wiki.restarters.net') {
    $('.wiki-nav-item').addClass('active');

    $('.nav-tabs-block li.nav-item a.nav-link').removeClass('active');

    $('.nav-tabs-block li.nav-item a.nav-link[href*="'+ window.location.pathname +'"]').each(function() {
      $(this).addClass('active');
    });
  }
});
}

// Start checking for jQuery
initWhenReady();
