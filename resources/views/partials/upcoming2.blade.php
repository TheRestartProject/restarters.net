<section class="dashboard__block">
    <div class="dashboard__block__header dashboard__block__header--events">
      <h4>@lang('partials.upcoming_events')</h4>
    </div>
    <div class="dashboard__block__content dashboard__block__content--table">
      <p>@lang('partials.upcoming_events_text_2')</p>
        <div class="table-responsive">
        <table role="table" class="table table-events table-striped">
            <thead>
                <tr>
                    <th style="font-family:Asap;font-weight:bold" scope="col">@lang('events.event_name')</th>
                    <th style="font-family:Asap;font-weight:bold" scope="col" class="cell-date">@lang('events.event_date')/@lang('events.event_time')</th>
                    <th style="font-family:Asap;font-weight:bold" scope="col" class="cell d-none d-sm-block">@lang('events.event_location')</th>
                </tr>
            </thead>
            <tbody>
              @if ( !$upcoming_events->isEmpty() )
                @foreach($upcoming_events as $event)
                  <tr>
                      <td class="cell-name">
                          <a href="/party/view/{{ $event->idevents }}">{{ $event->getEventName() }}</a>
                      </td>
                    <td class="cell-date">{{ $event->getEventDate('D jS M') }}<br>{{ $event->getEventStartEnd() }}</td>
                    <td class="d-none d-sm-block">{{ $event->location }}</td>
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
            <a href="{{ route('events') }}">@lang('partials.see_all_events')</a>
        </div>
    </div>
</section>
