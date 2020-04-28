<li class="volunteer-{{ $coordinator->id }}">

    <h3><a href="/profile/{{ $coordinator->id }}">{{ $coordinator->name }}</a></h3>

    <p><span class="badge badge-pill badge-primary">Coordinator</span></p>

    @php( $path = $coordinator->getProfile($coordinator->id)->path )
    @if ( is_null($path) )
      <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="Placeholder avatar" class="users-list__icon">
    @else
      <img src="{{ asset('/uploads/thumbnail_' . $path) }}" alt="{{ $coordinator->name }}'s avatar" class="users-list__icon">
    @endif

</li>
