<tr>
  <td class="table-cell-icon" colspan="1">
  @php( $group_image = $group->groupImage )
  @if( is_object($group_image) && is_object($group_image->image) )
    <img src="{{ asset('/uploads/thumbnail_' . $group_image->image->path) }}" alt="{{{ $group->name }}}">
  @else
    <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $group->name }}}">
  @endif
  </td>
  <td colspan="1"><a href="/group/view/{{{ $group->idgroups }}}" title="edit group">{{{ $group->name }}}</a></td>
  <td colspan="1">{{{ $group->getLocation() }}}</td>

  <td colspan="1" class="text-center">{{{ $group->all_hosts_count }}}</td>
  <td colspan="1" class="text-center">{{{ $group->all_restarters_count }}}</td>

  <td class="text-center" colspan="1">
    @php ($next_upcoming_event = $group->getNextUpcomingEvent())
    @if (is_null($next_upcoming_event))
      <p>@lang('groups.upcoming_none_planned')</p>
    @else
      <a href="/party/view/{{ $next_upcoming_event->idevents }}">
          <div>{{ $next_upcoming_event->getEventDate('D jS M Y') }}</div>
      </a>

    @endif
  </td>
  @if( ! is_null($groups) && FixometerHelper::hasRole(Auth::user(), 'Administrator'))
      <td colspan="1">
          {{ \Carbon\Carbon::parse($group->created_at)->diffForHumans() }}
      </td>
  @endif
</tr>
