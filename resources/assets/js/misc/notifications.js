function toggleRead(event) {
    event.preventDefault();

    $button = $(this);
    $counter = $('button.badge.badge-pill.badge-info span');

    $notificationsBadge = $('#notifications-badge');

    $.ajax({
      type: 'get',
      url: $button.attr('href'),
      success: function(data) {
        $button.parents('.card').addClass('status-is-read');
        $button.parents('.card').toggleClass('status-read');
        $counter.text( parseInt($counter.text()) - 1 );
        
        if(parseInt($counter.text()) == 0){
          $notificationsBadge.addClass('badge-no-notifications');
        }
        
      },
      error: function(error) {
        alert('Cannot mark as read, please report')
      }
    });
}

function showOlderNotifications(event) {
    event.preventDefault();

    $('.card.status-is-read').slideDown();
}

jQuery('.btn-marked').on('click',toggleRead);
jQuery('.js-load').on('click',showOlderNotifications);
