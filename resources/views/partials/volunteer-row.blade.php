<tr class="volunteer-{{ $volunteer->user }}">

  @php( $user = $volunteer->volunteer )

  @if( is_object($user) )
    <td class="table-cell-icon">
      @php( $path = $user->getProfile($user->id)->path )
      @if ( is_null($path) )
        <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="Placeholder avatar" class="rounded">
      @else
        <img src="{{ asset('/uploads/thumbnail_' . $path) }}" alt="{{ $user->name }}'s avatar" class="rounded">
      @endif
    </td>
    <td>
      <a href="/profile/{{ $user->id }}">{{ $user->name }}@if ( $volunteer->role == 3 )<span class="badge badge-primary">@lang('partials.host')</span>@endif</a>
    </td>
    @php( $user_skills = $user->userSkills )
    <td>
      @foreach( $user_skills as $skill )
         {{{ $skill->skillName->skill_name }}}<br>
      @endforeach
    </td>
  @else
    <td class="table-cell-icon">
      <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{ $volunteer->getFullName() }}'s avatar" class="users-list__icon">
    </td>
    <td>{{{ $volunteer->getFullName() }}}</td>
    <td>
      N/A
    </td>
  @endif

  @if( isset($type) )
    @if ( ( FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) ) || FixometerHelper::hasRole(Auth::user(), 'Administrator'))
      <td>
        <a href="#" class="users-list__remove js-remove" data-remove-volunteer="{{ $volunteer->user }}" data-event-id="{{ $volunteer->event }}" data-type="{{{ $type }}}">@lang('partials.remove_volunteer')</a>
      </td>
    @endif
  @endif
</tr>
