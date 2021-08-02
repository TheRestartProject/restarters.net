@php( $devices = $event->allDevices )
<tr>
    @if( !isset($group_view) )
        <td class="table-cell-icon d-none d-sm-table-cell">
        @php( $group = $event->theGroup ) @php( $group_image = $event->theGroup->groupImage ) @if( is_object($group_image) && is_object($group_image->image) )
        <a href="/group/view/{{ $group->idgroups }}"><img src="{{ asset('/uploads/thumbnail_' . $group_image->image->path) }}" alt="{{{ $group->name }}}" title="{{{ $group->name }}}"></a> @else
        <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $event->host->name }}}"> @endif
    </td>
    @endif
    <td class="cell-name">
        <a href="/party/view/{{ $event->idevents }}">{{ $event->getEventName() }}</a> @if ($event->online) <span class="badge badge-info">@lang('events.online_event')</span>@endif
        <div class="group-name"><a class="group-name" href="/group/view/{{ $event->theGroup->idgroups }}">{{ $event->theGroup->name }}</a></div>
    </td>
    <td class="cell-date">
        <div>{{ $event->getEventDate('D jS M Y') }}</div>
        <div>{{ $event->getEventStartEnd() }}</div>
    </td>
    @if( !isset($group_view) )
      <td class="cell-locations d-none d-sm-table-cell">
        @if( strlen($event->location) > 80 )
          <span data-toggle="popover" data-content="{{{ $event->location }}}" data-trigger="hover">
        @endif
        {{ Str::limit($event->location, 80, '...') }}
        @if( strlen($event->location) > 80 )
          </span>
        @endif
      </td>
    @endif
    @if( is_null($event->wordpress_post_id) ) @if( App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') )
        <td class="cell-moderation" colspan="8">Event requires <a href="/party/edit/{{ $event->idevents }}">moderation</a> by an admin</td>
    @else
    <td class="cell-moderation d-none d-sm-table-cell" colspan="8">@lang('partials.event_requires_moderation_by_an_admin')</td>
    @endif @elseif( $event->isUpcoming() )
    <td class="cell-figure d-none d-sm-table-cell">{{{ $event->allInvited->count() }}}</td>
    <td class="cell-figure d-none d-sm-table-cell">{{ $event->volunteers }}</td>
    <td class="cell-rsvp">
        @if ($event->isBeingAttendedBy(Auth::user()->id))
            <div>@lang('events.youre_going')</div>
        @else
            <a class="btn btn-primary" href="/party/view/{{ $event->idevents }}">RSVP</a>
        @endif
    </td>
    @endif
</tr>
