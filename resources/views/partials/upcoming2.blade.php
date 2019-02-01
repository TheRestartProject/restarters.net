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
                    <th scope="col" class="table-cell-icon"></th>
                    <th style="font-family:Asap;font-weight:bold" scope="col">@lang('events.event_name')</th>
                    <th style="font-family:Asap;font-weight:bold" scope="col" class="cell-date">@lang('events.event_date')/@lang('events.event_time')</th>
                    <th style="font-family:Asap;font-weight:bold" scope="col" class="cell-locations d-none d-sm-block">@lang('events.event_location')</th>
                </tr>
            </thead>
            <tbody>
              @if ( !$upcoming_events->isEmpty() )
                @foreach($upcoming_events as $event)
                  <tr>
                      <td class="table-cell-icon">
                                              @php( $group_image = $event->theGroup->groupImage )
                          @if( is_object($group_image) && is_object($group_image->image) )
                              <img style="display:inline-block;" src="{{ asset('/uploads/thumbnail_' . $group_image->image->path) }}" alt="{{{ $event->theGroup->name }}}" title="{{{ $event->theGroup->name }}}" />
                          @else
                              <img style="display:inline-block;padding-right:5px" src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $event->theGroup->name }}}">
                          @endif

                      </td>
                      <td class="cell-name">
                          <a href="/party/view/{{ $event->idevents }}">{{ $event->getEventName() }}</a>
                      </td>
                    <td class="cell-date">{{ $event->getEventDate('D jS M') }}<br>{{ $event->getEventStartEnd() }}</td>
                    <td class="d-none d-sm-block cell-locations">{{ $event->location }}</td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="4" style="text-align: center">@lang('partials.no_upcoming_events')</td>
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
