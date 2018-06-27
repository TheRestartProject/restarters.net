<section class="dashboard__block">
    <div class="dashboard__block__content dashboard__block__content--table">
      <h4>Upcoming events</h4>
      <p>If possible, the best way to get involved is to go to an event, see how it works, and participate</p>
        <div class="table-responsive">
        <table role="table" class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">@lang('events.event_name')</th>
                    <th scope="col" class="cell-date">@lang('events.event_datetime')</th>
                    <th scope="col" class="cell-locations">@lang('events.event_location')</th>
                </tr>
            </thead>
            <tbody>
              @if (!empty($closest_events))
                @foreach($closest_events as $closest_event)
                  <tr>
                      <td>{!! $closest_event->free_text !!}</td>
                      <td>{{ $closest_event->event_date }}</td>
                      <td><a href="">30 devices need attention</a></td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="3" style="text-align: center">No Upcoming Events</td>
                </tr>
              @endif
            </tbody>
        </table>
        </div>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="{{ url('/events') }}">See all events</a>
        </div>
    </div>
</section>
