function searchEventsByGroup() {
    $group_id = $(".change-group :selected").val();

    if ($group_id == null) {
        return false;
    }

    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $("input[name='_token']").val()
        },
        type: 'GET',
        url: '/api/groups/'+ $group_id + '/events?format=location',
        datatype: 'json',
        success: function(response) {
            console.log(response)
            $('.change-events option').remove();
            $events = JSON.parse(response.events)

            $.each($events, function($key, $event) {
                var data = {
                    id: $event.id,
                    text: $event.location
                };

                var newOption = new Option(data.text, data.id, false, false);
                $('.change-events').append(newOption).trigger('change');
            });

            console.log('Success: Found ' + $('.change-events option').length + ' events.');
        },
    });
}

$(document).ready(function() {
    searchEventsByGroup();

    $('.change-group').on('change', function() {
        searchEventsByGroup();
    });

    $('.change-events').on('change', function() {
        $('.change-event-url').attr('href', '/party/view/' + $(this).val() + '#devices-section');
    });
});
