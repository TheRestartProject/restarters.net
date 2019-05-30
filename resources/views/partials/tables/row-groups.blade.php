<tr>
  <td class="table-cell-icon">
  @php( $group_image = $group->groupImage )
  @if( is_object($group_image) && is_object($group_image->image) )
    <img src="{{ asset('/uploads/thumbnail_' . $group_image->image->path) }}" alt="{{{ $group->name }}}">
  @else
    <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $group->name }}}">
  @endif
  </td>
  <td><a href="/group/view/{{{ $group->idgroups }}}" title="edit group">{{{ $group->name }}}</a></td>
  <td>{{{ $group->getLocation() }}}</td>
  <td class="text-center">
    @php ($next_upcoming_event = $group->getNextUpcomingEvent())
    @if (is_null($next_upcoming_event))
      <p>None planned</p>
    @else
      <a href="/party/view/{{ $next_upcoming_event->idevents }}">{{ $next_upcoming_event->getEventName() }}</a>
    @endif
  </td>
  <td class="text-center">
    @if ( ! in_array($group->idgroups, $your_groups_uniques) )
      <a class="btn btn-primary" href="/group/join/{{ $group->idgroups }}" id="join-group">Follow</a>
    @endif
  </td>
  @if(  !is_null($groups) && FixometerHelper::hasRole(Auth::user(), 'Administrator'))
      <td>
          {{ \Carbon\Carbon::parse($group->created_at)->diffForHumans() }}
      </td>
  @endif
</tr>
