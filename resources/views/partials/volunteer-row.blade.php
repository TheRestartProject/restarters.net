<tr class="volunteer volunteer-{{ $volunteer->user }}">

  @php( $user = $volunteer->volunteer )

  @if( is_object($user) )
    <td class="table-cell-icon">
      @php( $path = $user->getProfile($user->id)->path )
      @if ( is_null($path) )
        <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="Placeholder avatar">
      @else
        <img src="{{ asset('/uploads/thumbnail_' . $path) }}" alt="{{ $user->name }}'s avatar">
      @endif
    </td>
    <td>
        <a href="/profile/{{ $user->id }}">{{ $user->name }}</a>
        @if ( $volunteer->role == 3 )
            <span class="badge badge-primary">@lang('partials.host')</span>
        @endif
    </td>
    @php( $user_skills = $user->userSkills )
    <td>
      @foreach( $user_skills as $skill )
         {{{ $skill->skillName->skill_name }}}.
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
    @if( $type == 'group' && ( FixometerHelper::hasRole( Auth::user(), 'Administrator' ) || $is_host_of_group ) )
      <td>
        @include('partials.volunteer-dropdown')
      </td>
    @elseif( $type != 'group' && ( ( FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) ) || FixometerHelper::hasRole(Auth::user(), 'Administrator') ) )
      <td align="right">
        <a href="#" class="users-list__remove js-remove" data-remove-volunteer="{{ $volunteer->user }}" data-idevents="{{ $volunteer->event }}" data-type="{{{ $type }}}">@lang('partials.remove_volunteer')</a>
      </td>
    @endif
  @endif

</tr>
