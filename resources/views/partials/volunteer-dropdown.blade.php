@if (isset($group_id) && (App\Helpers\Fixometer::userIsHostOfGroup($group_id, Auth::id()) || App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator')))
  <div class="btn-group d-block btn-group-volunteers">
    <button class="dropdown-toggle float-right" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Edit Volunteer
    </button>
    <div class="dropdown-menu dropdown-menu-right">
      @if ( $volunteer->role == 4 )
        <a class="dropdown-item" href="/group/make-host/{{ $group_id }}/{{ $user->id }}">Make Host</a>
      @endif
      <a class="dropdown-item" href="/group/remove-volunteer/{{ $group_id }}/{{ $user->id }}">Remove Volunteer</a>
    </div>
  </div>
@endif
