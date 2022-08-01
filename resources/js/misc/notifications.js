function toggleRead(event) {
    event.preventDefault();

    $button = $(this);
    $counter = $('#notifications-badge .chat-count');

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

jQuery('.btn-marked').on('click',toggleRead);
