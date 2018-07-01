@php( $user = $volunteer->eventUser )
@php( $user_skills = $user->userSkills )

<tr>
  <td class="table-cell-icon">
    @php( $path = $user->getProfile($user->id)->path )
    @if ( is_null($path) )
      <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="Placeholder avatar" class="rounded">
    @else
      <img src="{{ asset('/uploads/thumbnail_' . $path) }}" alt="{{ $user->name }}'s avatar" class="rounded">
    @endif
  </td>
  <td>
    <a href="/profile/{{ $user->id }}">
      {{ $user->name }}
      @if ( $volunteer->role == 3 )
        <span class="badge badge-primary">Host</span>
      @endif
    </a>
  </td>
  <td>
    @foreach( $user_skills as $skill )
       {{{ $skill->skillName->skill_name }}}<br>
    @endforeach
  </td>
</tr>
