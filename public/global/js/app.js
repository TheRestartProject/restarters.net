/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 178);
/******/ })
/************************************************************************/
/******/ ({

/***/ 178:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(179);


/***/ }),

/***/ 179:
/***/ (function(module, exports, __webpack_require__) {

// import jquery from 'jquery';
// window.$ = window.jQuery=jquery;

// window.bootstrap = require('bootstrap');

window.onload = function () {

  (function ($, window, document) {
    // Use strict mode to reduce development errors.
    "use strict";

    $(document).ready(function () {
      __webpack_require__(180);
      __webpack_require__(181);
      __webpack_require__(182);

      console.log('Global js ready!');

      // Keep hash within URL when toggling between Bootstrap Panes/Tabs
      $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if (history.pushState) {
          history.pushState(null, null, $(this).attr('href'));
        } else {
          location.hash = $(this).attr('href');
        }
      });

      $("form[id$='-search']").submit(function (e) {
        if ($('#formHash').length) {
          $('#formHash').val(window.location.hash);
        } else {
          $(this).append($('<input>', {
            type: 'hidden',
            id: 'formHash',
            name: 'formHash',
            val: window.location.hash
          }));
        }
      });
    });

    // Change Bootstrap Pane/Tab view onload where hash is within URL
    var hash = window.location.hash;

    if ($('#formHash').length) {
      var hash = $('#formHash').val();
    }

    if (hash != '' || hash != undefined) {
      var $element = $('a[href="' + hash + '"]');
      if ($element.length == 1) {
        $element.tab('show');
      }
    }

    // TODO: how to get this from .env?
    if (window.location.origin == 'https://wiki.restarters.dev' || window.location.origin == 'https://wiki.restarters.net') {
      $('.wiki-nav-item').addClass('active');

      $('.nav-tabs-block li.nav-item a.nav-link').removeClass('active');

      $('.nav-tabs-block li.nav-item a.nav-link[href*="' + window.location.pathname + '"]').each(function () {
        $(this).addClass('active');
      });
    }
  })(jQuery, window, document);
};

/***/ }),

/***/ 180:
/***/ (function(module, exports) {

$('.toggle-dropdown-menu').click(function () {

  // If item is already active then close all.
  if ($(this).hasClass('dropdown-active')) {
    $('.toggle-dropdown-menu').each(function () {
      $(this).removeClass('dropdown-active');
      $(this).parents().children('.dropdown-menu-items').hide();
    });

    return false;
  }

  // Close all existing items except current.
  $('.toggle-dropdown-menu').not(this).each(function () {
    $(this).removeClass('dropdown-active');
    $(this).parents().children('.dropdown-menu-items').hide();
  });

  // Show items.
  $(this).toggleClass('dropdown-active');
  $(this).parents().children('.dropdown-menu-items').show();
});

/***/ }),

/***/ 181:
/***/ (function(module, exports) {

// API call to current site - check for notifications
function ajaxSearchNotifications() {
  // $base_url = window.location.host;
  $notification_menu_items = $('.notification-menu-items');
  $notification_menu_items.hide();
  $('.toggle-notifications-menu .bell-icon-active').hide();

  $url = 'https://restarters.dev' + '/test/discourse/notifications';

  $.ajax({
    headers: {
      // 'X-CSRF-TOKEN': $("input[name='_token']").val(),
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    xhrFields: {
      withCredentials: true
    },
    type: 'GET',
    url: $url,
    datatype: 'json',
    success: function success(response) {
      console.log('Success: connected to Discourse.');

      // Response failed
      if (response.message == 'failed') {
        console.log('Success: failed to find any new notifications.');
        return false;
      }

      // If notifications exist then we can create a cookie
      var $notifications = response.notifications;

      if (Object.keys($notifications).length > 0) {
        console.log('Success: notifications found on Discourse.');

        $notification_menu_items.css('display', '');
        $notification_menu_items.empty();
        $('.toggle-notifications-menu .bell-icon-active').css('display', '');

        $.each($notifications, function (index, $notification) {
          $notification_menu_items.append($('<li>').append($('<a>').attr('href', 'https://restarters.dev/notifications/' + $notification.id).text($notification.data.title)).attr('class', 'notifcation-text'));
        });
      }
    }
  });
}

//ajaxSearchNotifications();

/***/ }),

/***/ 182:
/***/ (function(module, exports) {

// API call to current site - check for user authenticated
function checkAuth() {
  $url = 'https://restarters.dev' + '/test/check-auth';

  $notifications_list_item = $('.notifications-list-item').hide();
  $auth_menu_items = $('.auth-menu-items').hide();
  $auth_menu_items.removeClass('dropdown-menu-items');

  $.ajax({
    headers: {
      // 'X-CSRF-TOKEN': $("input[name='_token']").val(),
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    xhrFields: {
      withCredentials: true
    },
    type: 'GET',
    url: $url,
    datatype: 'json',
    success: function success(response) {
      $auth_list_item = $('.auth-list-item');

      var response = response.data;

      $main_navigation_dropdown = $('.main-nav-dropdown');

      if (response.authenticated !== null && response.authenticated !== undefined) {
        $.each(response.menu.reporting, function (key, value) {
          var spacer_condition = key.includes('spacer');

          var header_condition = key.includes('header');

          if (header_condition) {
            $main_navigation_dropdown.append($('<li>').attr('class', 'dropdown-menu-header').text(value));
          } else if (spacer_condition) {
            $main_navigation_dropdown.append($('<li>').attr('class', 'dropdown-spacer'));
          } else {
            $main_navigation_dropdown.append($('<li>').append($('<a>').attr('href', value).text(key)));
          }
        });

        $('.regular-user-svg').addClass('d-none');
        $('.authenticated-user-svg').removeClass('d-none');

        $.each(response.menu.user, function (key, value) {
          var spacer_condition = key.includes('spacer');

          var header_condition = key.includes('header');

          if (header_condition) {
            $auth_menu_items.append($('<li>').attr('class', 'dropdown-menu-header').text(value));
          } else if (spacer_condition) {
            $auth_menu_items.append($('<li>').attr('class', 'dropdown-spacer'));
          } else {
            $auth_menu_items.append($('<li>').append($('<a>').attr('href', value).text(key)));
          }
        });

        if ($notifications_list_item.length) {
          $notifications_list_item.css('display', '');
        }

        if ($auth_list_item.length) {
          $auth_menu_items.addClass('dropdown-menu-items');
          $auth_menu_items.css('display', '');
        }
      } else {
        $auth_list_item.find('a').attr('href', 'https://restarters.dev');
      }

      // Amend Main navigation dropdown links
      $.each(response.menu.general, function (key, value) {
        $main_navigation_dropdown.append($('<li>').append($('<a>').attr('href', value).text(key)));
      });
    }
  });
}

//checkAuth();

/***/ })

/******/ });