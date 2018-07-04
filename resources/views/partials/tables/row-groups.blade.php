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
  <td class="text-center">{{{ $group->allHosts->count() }}}</td>
  <td class="text-center">{{{ $group->allRestarters->count() }}}</td>
</tr>
