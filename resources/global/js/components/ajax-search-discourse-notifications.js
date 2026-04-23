import jQuery from 'jquery';
const $ = jQuery;

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
    success: function(response) {
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

        $.each($notifications, function(index, $notification) {
          $notification_menu_items.append(
            $('<li>').append(
              $('<a>').attr('href', 'https://restarters.dev/notifications/' + $notification.id).text($notification.data.title)
            ).attr('class', 'notifcation-text')
          );
        });
      }
    },
  });
}

//ajaxSearchNotifications();
