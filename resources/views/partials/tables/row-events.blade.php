@php( $devices = $event->allDevices )
<tr>
    <td class="table-cell-icon">
      @php( $group_image = $event->host->hostImage )
      @if( is_object($group_image) )
        <img src="{{ asset('/uploads/thumbnail_' . $group_image->image->path) }}" alt="{{{ $event->host->name }}}">
      @else
        <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $event->host->name }}}">
      @endif
    </td>
    <td class="cell-name"><a href="/party/view/{{ $event->idevents }}">{{ $event->getEventName() }}</a></td>
    <td class="cell-date">{{ $event->getEventDate() }}</td>
    <td class="cell-date">{{ $event->getEventStartEnd() }}</td>
    <td class="cell-locations">{{ $event->location }}</td>
    @if( is_null($event->wordpress_post_id) )
      @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') )
        <td class="cell-moderation" colspan="8">Event requires <a href="/party/edit/{{ $event->idevents }}">moderation</a> by an admin</td>
      @else
        <td class="cell-moderation" colspan="8">Event requires moderation by an admin</td>
      @endif
    @elseif( $event->isUpcoming() )
      <td class="cell-figure">{{{ $event->allInvited->count() }}}</td>
      <td class="cell-rsvp" colspan="{{{ $invite === true ? 7 : 6 }}}">This event hasn't started <a href="/party/view/{{ $event->idevents }}">RSVP</a></td>
    @elseif( !empty($devices) )
      @php( $stats = $event->getEventStats($EmissionRatio) )
      <td class="cell-figure">{{ $event->pax }}</td>
      <td class="cell-figure">{{ $event->volunteers }}</td>
      <td class="cell-figure">{{{ $stats['ewaste'] }}}<small>kg<small></td>
      <td class="cell-figure">{{{ $stats['co2'] }}}<small>kg<small></td>
      <td class="cell-figure">{{{ $stats['fixed_devices'] }}}</td>
      <td class="cell-figure">{{{ $stats['repairable_devices'] }}}</td>
      <td class="cell-figure">{{{ $stats['dead_devices'] }}}</td>
    @elseif( $event->isInProgress() )
      <td class="cell-figure">{{ $event->pax }}</td>
      <td class="cell-figure">{{ $event->volunteers }}</td>
      <td class="cell-progress" colspan="{{{ $invite === true ? 6 : 5 }}}">No devices added <a href="/party/view/{{ $event->idevents }}#devices">Add a device</a></td>
    @elseif( $event->hasFinished() )
      <td class="cell-figure">{{ $event->pax }}</td>
      <td class="cell-figure">{{ $event->volunteers }}</td>
      <td class="cell-progress" colspan="{{{ $invite === true ? 6 : 5 }}}">No devices added <a href="/party/view/{{ $event->idevents }}#devices">Add a device</a></td>
    @endif
</tr>
