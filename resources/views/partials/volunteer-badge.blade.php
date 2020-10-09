<li class="volunteer-{{ $volunteer->user }}">

    @php( $user = $volunteer->volunteer )

    @if( is_object($user) )

      @php( $user_skills = $user->userSkills )

      <?php
        $skills_list = '';
        foreach( $user_skills as $skill ){
          $skills_list .= $skill->skillName->skill_name.', ';
        }
        $skills_list = rtrim($skills_list, ', ');
      ?>

      <h3><a href="/profile/{{ $user->id }}">{{ $user->name }}</a></h3>

      @if ( $volunteer->role == 3 )
        <p><span class="badge badge-pill badge-primary">@lang('groups.host')</span></p>
      @else
        <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="{{{ $skills_list }}}">{{{ count($user_skills) . ' ' . str_plural('skill', count($user_skills)) }}}</button></p>
      @endif

    @else

      <h3>{{{ $volunteer->getFullName() }}}</h3>

    @endif

    @if( isset($type) )
      @if ( ( FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) ) || FixometerHelper::hasRole(Auth::user(), 'Administrator'))
        <button class="users-list__remove js-remove volunteer-{{ $volunteer->user }}" data-remove-volunteer="{{ $volunteer->user }}" data-idevents="{{ $volunteer->event }}" data-type="{{{ $type }}}">@lang('partials.remove_volunteer')</button>
      @endif
    @endif

    @include('partials.volunteer-dropdown')

    @if( is_object($user) )
      @php( $path = $user->getProfile($user->id)->path )
      @if ( is_null($path) )
        <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="Placeholder avatar" class="users-list__icon">
      @else
        <img src="{{ asset('/uploads/thumbnail_' . $path) }}" alt="{{ $user->name }}'s avatar" class="users-list__icon">
      @endif
    @else
      <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{ $volunteer->getFullName() }}'s avatar" class="users-list__icon">
    @endif

</li>
