function toggleRead(event) {
    event.preventDefault();

    const $ = window.jQuery || window.$;
    if (!$) return;

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

// Ensure jQuery is available from global scope since it's loaded via CDN
// Use event delegation to handle dynamically loaded notification elements
if (typeof window !== 'undefined') {
    const initNotifications = () => {
        const $ = window.jQuery || window.$;
        if (!$) return;

        // Use event delegation so it works for dynamically loaded notifications
        $(document).on('click', '.btn-marked', toggleRead);
    };

    // Initialize when DOM is ready, or immediately if already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotifications);
    } else {
        initNotifications();
    }
}
