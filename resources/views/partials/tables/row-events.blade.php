@php( $devices = $event->allDevices )
<tr>
    @if( !isset($group_view) )

      <td class="hightlighted {{ $event->VisuallyHighlight() }}"></td>

      <td class="table-cell-icon">
        @php( $group = $event->theGroup )
        @php( $group_image = $event->theGroup->groupImage )
        @if( is_object($group_image) && is_object($group_image->image) )
            <a class="mx-auto" href="/group/view/{{ $group->idgroups }}"><img src="{{ asset('/uploads/thumbnail_' . $group_image->image->path) }}" alt="{{{ $group->name }}}" title="{{{ $group->name }}}"></a>
        @else
          <img class="mx-auto" src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $event->host->name }}}">
        @endif
      </td>
    @endif

    <td class="cell-name">
        <a href="/party/view/{{ $event->idevents }}">{{ $event->getEventName() }}</a>
        @if( !isset($group_view) )
            <div class="group-name"><a class="group-name" href="/group/view/{{ $event->theGroup->idgroups }}">{{ $event->theGroup->name }}</a></div>
        @endif
    </td>

    <td class="cell-date">
        {{ $event->getEventDate('D jS M Y') }}
        {{ $event->getEventStartEnd() }}
    </td>

    <td class="cell-figure">{{ $event->pax }}</td>
    <td class="cell-figure">{{ $event->volunteers }}</td>


    {{-- Event requires moderation by an admin --}}
    @if( $event->requiresModerationByAdmin() )
      @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') )
        <td class="event-requires-moderation-by-admin-cell" colspan="5">Event requires <a href="/party/edit/{{ $event->idevents }}">moderation</a> by an admin</td>
      @else
        <td class="event-requires-moderation-by-admin-cell" colspan="5">@lang('partials.event_requires_moderation_by_an_admin')</td>
      @endif
    @endif

    {{-- Event is currently in-progress --}}
    @if ( $event->isInProgress() )
      <td class="bg-success text-center" colspan="5">
        This event is in progress <a href="/party/view/{{ $event->idevents }}">Add a device</a>
      </td>
    @endif

    {{-- Event has not started RSVP --}}
    @if ( $event->isUpcoming() || $event->isInProgress() && ! $event->hasStartedRSVP() )
      <td class="event-in-progress-rsvp-cell text-center" colspan="5">
        <a href="/party/join/{{ $event->idevents }}" class="btn btn-primary btn-save">RSVP</a>
      </td>
    @endif

    {{-- Event has finished and does not have any devices --}}
    @if ( $event->hasFinished() && $event->allDevices->isEmpty() )
      <td class="event-has-no-devices-cell text-center" colspan="5">
        No devices added <a href="/party/view/{{ $event->idevents }}">Add a device</a>
      </td>
    @endif

    @if( $event->hasFinished() && ! $event->allDevices->isEmpty() )
      @php( $stats = $event->getEventStats($EmissionRatio) )
      <td class="cell-figure">{{{ number_format(round($stats['ewaste']), 0) }}}<small>kg<small></td>
      <td class="cell-figure">{{{ number_format(round($stats['co2']), 0) }}}<small>kg<small></td>
      <td class="cell-figure">{{{ $stats['fixed_devices'] }}}</td>
      <td class="cell-figure">{{{ $stats['repairable_devices'] }}}</td>
      <td class="cell-figure">{{{ $stats['dead_devices'] }}}</td>
    @endif

</tr>
