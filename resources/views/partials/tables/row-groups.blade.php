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
    <?php

      $hosts = $group->allHosts;
      $return = '';

      foreach( $hosts as $host ){
        $return .= trim($host->volunteer->name). ', ';
      }

    ?>
    <span data-toggle="popover" data-content="{{{ rtrim($return, ', ') }}}" data-trigger="hover">
      {{{ $group->allHosts->count() }}}
    </span>
  </td>
  <td class="text-center">
    <?php

      $restarters = $group->allRestarters;
      $return = '';

      foreach( $restarters as $restarter ){
        $return .= trim($restarter->volunteer->name). ', ';
      }

    ?>
    <span data-toggle="popover" data-content="{{{ rtrim($return, ', ') }}}" data-trigger="hover">
      {{{ $group->allRestarters->count() }}}
    </span>
  </td>
</tr>
