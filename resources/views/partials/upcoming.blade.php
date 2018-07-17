<section class="dashboard__block">

    <div class="dashboard__block__content dashboard__block__content--table">
      <h4>@lang('partials.upcoming_events')</h4>
      <p>@lang('partials.upcoming_events_text')</p>
        <div class="table-responsive">
        <table role="table" class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">@lang('events.event_name')</th>
                    <th scope="col" class="cell-date">@lang('events.event_date')/@lang('events.event_time')</th>
                    <th scope="col" class="cell-locations">@lang('events.event_location')</th>
                </tr>
            </thead>
            <tbody>
              @if ( !$upcoming_events->isEmpty() )
                @foreach($upcoming_events as $event)
                  <tr>
                    <td class="cell-name"><a href="/party/view/{{ $event->idevents }}">{{ $event->getEventName() }}</a></td>
                    <td class="cell-date">{{ $event->getEventDate() }}<br>{{ $event->getEventStartEnd() }}</td>
                    <td class="cell-locations">{{ $event->location }}</td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="3" style="text-align: center">@lang('partials.no_upcoming_events')</td>
                </tr>
              @endif
            </tbody>
        </table>
        </div>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="{{ url('/events') }}">@lang('partials.see_all_events')</a>
        </div>
    </div>
</section>
