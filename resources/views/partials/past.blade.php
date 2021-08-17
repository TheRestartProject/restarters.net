<section class="dashboard__block">

    <div class="dashboard__block__content dashboard__block__content--table">
        <h4>@lang('partials.your_recent_events')</h4>
        @if ( App\Helpers\Fixometer::hasRole(Auth::user(), 'Restarter') )
          <p>@lang('partials.your_recent_events_txt1')</p>
        @else
          <p>@lang('partials.your_recent_events_txt2')</p>
        @endif
        <div class="table-responsive">
        <table role="table" class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">@lang('events.event_name')</th>
                    <th scope="col" class="cell-date d-none d-sm-table-cell">@lang('events.event_date')</th>
                    <th scope="col" class=""></th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($past_events))
                  @foreach($past_events as $past_event)
                    <tr>
                        <td class="cell-name"><a href="/party/view/{{ $past_event->idevents }}">{{ $past_event->getEventName() }}</a></td>
                        <td class="d-none d-sm-table-cell">{{ $past_event->getEventDate() }}</td>
                        @if( $past_event->allDevices->count() == 0 )
                          <td><a href="/party/view/{{{ $past_event->idevents }}}#devices">@lang('dashboard.log_devices')</a></td>
                        @else
                          <td>{{ $past_event->allDevices->count() }} @lang('dashboard.devices_logged')</td>
                        @endif
                    </tr>
                  @endforeach
                @else
                  <tr>
                    <td colspan="3" style="text-align: center">@lang('partials.no_past_events')</td>
                  </tr>
                @endif
            </tbody>
        </table>
        </div>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="{{ url('/party') }}">@lang('partials.see_all_events')</a>
        </div>
    </div>
</section>
