<section class="dashboard__block">

    <div class="dashboard__block__content dashboard__block__content--table">
        <h4>Your recent events</h4>
        @if ( FixometerHelper::hasRole(Auth::user(), 'Restarter') )
          <p>These are events you RSVP'ed to, or where a host logged your participation.</p>
        @else
          <p>Here's a list of recent events you have been a part of - all important contributions to your community and the environment. </p>
        @endif
        <div class="table-responsive">
        <table role="table" class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">@lang('events.event_name')</th>
                    <th scope="col" class="cell-date">@lang('events.event_date')</th>
                    <th scope="col" class="cell-locations"></th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($past_events))
                  @foreach($past_events as $past_event)
                    <tr>
                        <td class="cell-name"><a href="/party/view/{{ $past_event->idevents }}">{{ $past_event->getEventName() }}</a></td>
                        <td>{{ $past_event->getEventDate() }}</td>
                        @if( $past_event->allDevices->count() == 0 )
                          <td><a href="/party/view/{{{ $past_event->eventid }}}#devices">Add a device</a></td>
                        @else
                          <td>{{ $past_event->allDevices->count() }} @lang('dashboard.devices_logged')</td>
                        @endif
                    </tr>
                  @endforeach
                @else
                  <tr>
                    <td colspan="3" style="text-align: center">No Past Events</td>
                  </tr>
                @endif
            </tbody>
        </table>
        </div>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="{{ url('/party') }}">See all events</a>
        </div>
    </div>
</section>
