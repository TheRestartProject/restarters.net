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
      $hosts = $group->allConfirmedHosts;
      $return = '';

      foreach( $hosts as $host ){
        $return .= trim($host->volunteer->name). ', ';
      }
    ?>
    <span data-toggle="popover" data-content="{{{ rtrim($return, ', ') }}}" data-trigger="hover">
      {{{ $group->allConfirmedHosts->count() }}}
    </span>
  </td>
  <td class="text-center">
    <?php

      $restarters = $group->allConfirmedRestarters;
      $return = '';

      foreach( $restarters as $restarter ){
        $return .= trim($restarter->volunteer->name). ', ';
      }

    ?>
    <span data-toggle="popover" data-content="{{{ rtrim($return, ', ') }}}" data-trigger="hover">
      {{{ $group->allConfirmedRestarters->count() }}}
    </span>
  </td>
  @if(  !is_null($groups) && FixometerHelper::hasRole(Auth::user(), 'Administrator'))
      <td>
          {{ \Carbon\Carbon::parse($group->created_at)->diffForHumans() }}
      </td>
  @endif
</tr>
