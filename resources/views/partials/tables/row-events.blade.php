@php( $devices = $event->allDevices )
<tr>
    <td class="hightlighted {{ $event->VisuallyHighlight() }}"></td>

    @if( !isset($group_view) )
      <td class="table-cell-icon d-none d-sm-table-cell text-center">
        @php( $group = $event->theGroup )
        @php( $group_image = $event->theGroup->groupImage )
        @if( is_object($group_image) && is_object($group_image->image) )
            <a class="mx-auto" href="/group/view/{{ $group->idgroups }}"><img src="{{ asset('/uploads/thumbnail_' . $group_image->image->path) }}" alt="{{{ $group->name }}}" title="{{{ $group->name }}}"></a>
        @else
          <img class="mx-auto text-center" src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $event->host->name }}}">
        @endif
      </td>
    @endif

    <td class="cell-name">
        <a href="/party/view/{{ $event->idevents }}">{{ $event->getEventName() }}</a>  @if ($event->online) <span class="badge badge-info">@lang('events.online_event')</span>@endif
        @if( !isset($group_view) )
            <div class="group-name"><a class="group-name" href="/group/view/{{ $event->theGroup->idgroups }}">{{ $event->theGroup->name }}</a></div>
        @endif
    </td>

    <td class="cell-date">
        {{ $event->getEventDate('D jS M Y') }}
        <br>
        {{ $event->getEventStartEnd() }}
    </td>


    @if ( isset($show_invites_count) && $show_invites_count == true )
      <td class="d-none d-sm-table-cell cell-figure cell-no_of_invites">{{{ $event->allInvited->count() }}}</td>
    @else
      <td class="d-none d-sm-table-cell cell-figure cell-no_of_participants @if ( $event->checkForMissingData()['participants_count'] == 0 ) cell-danger @endif">{{ $event->pax }}</td>
    @endif

    @if ( $event->isUpcoming() || $event->isInProgress() )
      <td class="d-none d-sm-table-cell cell-figure cell-no_of_restarters">{{ $event->checkForMissingData()['volunteers_count'] }}</td>
    @else
      <td class="d-none d-sm-table-cell cell-figure cell-no_of_restarters @if ( $event->checkForMissingData()['volunteers_count'] <= 1 ) cell-danger @endif">{{ $event->checkForMissingData()['volunteers_count'] }}</td>
    @endif


    @if( $event->requiresModerationByAdmin() )




      @if( App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') || App\Helpers\Fixometer::hasRole(Auth::user(), 'NetworkCoordinator') )
        <td class="d-none d-sm-table-cell cell-warning text-center">Event requires <a href="/party/edit/{{ $event->idevents }}">moderation</a></td>
      @else
        <td class="d-none d-sm-table-cell cell-warning text-center">@lang('partials.event_requires_moderation_by_an_admin')</td>
      @endif



    @elseif ( $event->isUpcoming() && ! $event->isStartingSoon() )



      @if ( $event->isVolunteer() )
        <td class="d-none d-sm-table-cell text-center">
          @lang('events.youre_going')
        </td>
      @else
        <td class="d-none d-sm-table-cell cell-warning text-center">
          <a href="/party/join/{{ $event->idevents }}" class="btn btn-primary">RSVP</a>
        </td>
      @endif



    @elseif( $event->isStartingSoon() )



      @if ( $event->isVolunteer() )
        <td class="d-none d-sm-table-cell cell-info text-center">
          @lang('events.youre_going')
        </td>
      @else
        <td class="d-none d-sm-table-cell cell-info text-center">
          <a href="/party/join/{{ $event->idevents }}" class="btn btn-primary">RSVP</a>
        </td>
      @endif



    @elseif( $event->isInProgress() )



      @if ( $event->isVolunteer() )
        <td class="d-none d-sm-table-cell cell-info text-center">
          <a href="/party/view/{{ $event->idevents }}" class="btn btn-primary">Add a device</a>
        </td>
      @else
        <td class="d-none d-sm-table-cell cell-success text-center">
          <a href="/party/join/{{ $event->idevents }}" class="btn btn-primary">RSVP</a>
        </td>
      @endif



    @elseif( $event->hasFinished() )

      @if ( $event->checkForMissingData()['devices_count'] != 0  )
        @php( $stats = $event->getEventStats() )
        <td class="d-none d-sm-table-cell cell-figure">{{{ number_format(round($stats['ewaste']), 0) }}}<small>kg<small></td>
        <td class="d-none d-sm-table-cell cell-figure">{{{ number_format(round($stats['co2']), 0) }}}<small>kg<small></td>
        <td class="d-none d-sm-table-cell cell-figure">{{{ $stats['fixed_devices'] }}}</td>
        <td class="d-none d-sm-table-cell cell-figure">{{{ $stats['repairable_devices'] }}}</td>
        <td class="d-none d-sm-table-cell cell-figure">{{{ $stats['dead_devices'] }}}</td>
      @else
        <td class="d-none d-sm-table-cell cell-danger text-center" colspan="5">
          @lang('partials.no_devices_added') <a href="/party/view/{{ $event->idevents }}">@lang('partials.add_a_device')</a>
        </td>
      @endif



    @endif
</tr>
