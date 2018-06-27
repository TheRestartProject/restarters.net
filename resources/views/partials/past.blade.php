<section class="dashboard__block">

    <div class="dashboard__block__content dashboard__block__content--table">
        <h4>Your past events</h4>
        @if ( FixometerHelper::hasRole(Auth::user(), 'Restarter') )
          <p>These are events you RSVP'ed to, or where a host logged your participation.</p>
        @else
          <p>Here's a list of past events you have helped organise, all important contributions to your community and the environment. </p>
        @endif
        <div class="table-responsive">
        <table role="table" class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">@lang('events.event_name')</th>
                    <th scope="col">Date</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($past_events))
                  @foreach($past_events as $past_event)
                    <tr>
                        <td>{{ $past_event->venue }}</td>
                        <td>{{ $past_event->event_date }}</td>
                        <td><a href="">30 devices need attention</a></td>
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
            <a href="{{ url('/devices') }}">See all devices</a>
        </div>
    </div>
</section>
