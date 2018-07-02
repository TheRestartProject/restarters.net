<section class="dashboard__block">
    <div class="dashboard__block__content dashboard__block__content--table">
      <h4>Upcoming events</h4>
      <p>Find and attend events.  If you have entered your town/city in your profile, you will see the events nearest to you.</p>
        <div class="table-responsive">
        <table role="table" class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">@lang('events.event_name')</th>
                    <th scope="col" class="cell-date">@lang('events.event_date')</th>
                    <th scope="col" class="cell-date">@lang('events.event_time')</th>
                    <th scope="col" class="cell-locations">@lang('events.event_location')</th>
                </tr>
            </thead>
            <tbody>
              @if ( !$upcoming_events->isEmpty() )
                @foreach($upcoming_events as $event)
                  <tr>
                    <td class="cell-name"><a href="/party/view/{{ $event->idevents }}">{{ $event->getEventName() }}</a></td>
                    <td class="cell-date">{{ $event->getEventDate() }}</td>
                    <td class="cell-date">{{ $event->getEventStartEnd() }}</td>
                    <td class="cell-locations">{{ $event->location }}</td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="4" style="text-align: center">No Upcoming Events</td>
                </tr>
              @endif
            </tbody>
        </table>
        </div>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="{{ route('events') }}">See all events</a>
        </div>
    </div>
</section>
