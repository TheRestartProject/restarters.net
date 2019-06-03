@php( $devices = $event->allDevices )
<tr>
    @if( !isset($group_view) )
      <td class="table-cell-icon">
        @php( $group = $event->theGroup )
        @php( $group_image = $event->theGroup->groupImage )
        @if( is_object($group_image) && is_object($group_image->image) )
            <a href="/group/view/{{ $group->idgroups }}"><img src="{{ asset('/uploads/thumbnail_' . $group_image->image->path) }}" alt="{{{ $group->name }}}" title="{{{ $group->name }}}"></a>
        @else
          <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $event->host->name }}}">
        @endif
      </td>
    @endif

    <td class="cell-name">
        @if( !isset($group_view) )
            <div class="group-name"><a class="group-name" href="/group/view/{{ $event->theGroup->idgroups }}">{{ $event->theGroup->name }}</a></div>
        @endif
        <a href="/party/view/{{ $event->idevents }}">{{ $event->getEventName() }}</a>
    </td>

    <td class="cell-date">
        {{ $event->getEventDate('D jS M Y') }}
        {{ $event->getEventStartEnd() }}
    </td>

    <td class="cell-figure">{{ $event->pax }}</td>
    <td class="cell-figure">{{ $event->volunteers }}</td>


    {{-- Event requires moderation by an admin --}}
    @if( is_null($event->wordpress_post_id) )

      @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') )
        <td class="cell-moderation" colspan="5">Event requires <a href="/party/edit/{{ $event->idevents }}">moderation</a> by an admin</td>
      @else
        <td class="cell-moderation" colspan="5">@lang('partials.event_requires_moderation_by_an_admin')</td>
      @endif

    {{-- Event is currently in-progress --}}
    @elseif ( $event->isInProgress() )
      <td class="bg-success text-center" colspan="5">
        This event is in progress <a href="/party/view/{{ $event->idevents }}">Add a device</a>
      </td>

    {{-- Event has not started RSVP --}}
    @elseif ( $event->isUpcoming() || $event->isInProgress() && ! $event->hasStartedRSVP() )
      <td class="bg-warning text-center" colspan="5">
        This event hasn't started <a href="/party/view/{{ $event->idevents }}">RSVP</a>
      </td>

    {{-- Event has finished and does not have any devices --}}
    @elseif ( $event->hasFinished() && empty($event->allDevices) )
      <td class="bg-danger text-center" colspan="5">
        No devices added <a href="/party/view/{{ $event->idevents }}">Add a device</a>
      </td>
    @else
      @php( $stats = $event->getEventStats($EmissionRatio) )
      <td class="cell-figure">{{{ number_format(round($stats['ewaste']), 0) }}}<small>kg<small></td>
      <td class="cell-figure">{{{ number_format(round($stats['co2']), 0) }}}<small>kg<small></td>
      <td class="cell-figure">{{{ $stats['fixed_devices'] }}}</td>
      <td class="cell-figure">{{{ $stats['repairable_devices'] }}}</td>
      <td class="cell-figure">{{{ $stats['dead_devices'] }}}</td>
    @endif




    {{-- @if( is_null($event->wordpress_post_id) )
      @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') )
        <td class="cell-moderation" colspan="8">Event requires <a href="/party/edit/{{ $event->idevents }}">moderation</a> by an admin</td>
      @else
        <td class="cell-moderation" colspan="8">@lang('partials.event_requires_moderation_by_an_admin')</td>
      @endif
    @elseif( $event->isUpcoming() )
      <td class="cell-figure">{{{ $event->allInvited->count() }}}</td>
      <td class="cell-rsvp" colspan="{{{ $invite === true ? 7 : 6 }}}">@lang('partials.this_event_hasnt_started') <a href="/party/view/{{ $event->idevents }}">RSVP</a></td>
    @elseif( !empty($devices) )
      @php( $stats = $event->getEventStats($EmissionRatio) )
      <td class="cell-figure">{{ $event->pax }}</td>
      <td class="cell-figure">{{ $event->volunteers }}</td>
      <td class="cell-figure">{{{ number_format(round($stats['ewaste']), 0) }}}<small>kg<small></td>
      <td class="cell-figure">{{{ number_format(round($stats['co2']), 0) }}}<small>kg<small></td>
      <td class="cell-figure">{{{ $stats['fixed_devices'] }}}</td>
      <td class="cell-figure">{{{ $stats['repairable_devices'] }}}</td>
      <td class="cell-figure">{{{ $stats['dead_devices'] }}}</td>
    @elseif( $event->isInProgress() )
      <td class="cell-figure">{{ $event->pax }}</td>
      <td class="cell-figure">{{ $event->volunteers }}</td>
      <td class="cell-progress" colspan="{{{ $invite === true ? 6 : 5 }}}">@lang('partials.no_devices_added') <a href="/party/view/{{ $event->idevents }}#devices">@lang('partials.add_a_device')</a></td>
    @elseif( $event->hasFinished() )
      <td class="cell-figure">{{ $event->pax }}</td>
      <td class="cell-figure">{{ $event->volunteers }}</td>
      <td class="cell-progress" colspan="{{{ $invite === true ? 6 : 5 }}}">@lang('partials.no_devices_added') <a href="/party/view/{{ $event->idevents }}#devices">@lang('partials.add_a_device')</a></td>
    @endif --}}
</tr>
