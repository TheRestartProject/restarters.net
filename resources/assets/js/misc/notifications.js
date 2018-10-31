function toggleRead(event) {
    event.preventDefault();

    $button = $(this);

    $.ajax({
      type: 'get',
      url: $button.attr('href'),
      success: function(data) {
        $button.parents('.card').addClass('status-is-read');
        $button.parents('.card').toggleClass('status-read');
      },
      error: function(error) {
        alert('Cannot mark as read, please report')
      }
    });
}

jQuery('.btn-marked').on('click',toggleRead);
