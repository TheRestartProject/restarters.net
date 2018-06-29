<tr>
    <td class="table-cell-icon"><img src="{{ asset('/images/placeholder.png') }}" alt="Placeholder"></td>
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
      <td class="cell-figure">TBC</td>
      <td class="cell-rsvp" colspan="{{{ $invite === true ? 7 : 6 }}}">This event hasn't started <a href="/party/view/{{ $event->idevents }}">RSVP</a></td>
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
