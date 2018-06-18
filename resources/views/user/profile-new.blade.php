@extends('layouts.app')
@section('content')
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-6">
        <div class="row">
          <div class="col-sm-12 col-md-3">
            @if (!isset($user->path) || is_null($user->path))
              <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="Profile Picture" class="img-fluid rounded-circle">
            @else
              <img src="/uploads/{{ $user->path }}" alt="Profile Picture" class="img-fluid rounded-circle">
            @endif
          </div>
          <div class="col-sm-12 col-md-9 d-flex">
            <div class="align-self-center">
              <h3>{{ $user->name }}</h3>
              <p>{{ FixometerHelper::getRoleName($user->role) }}, @if (!empty($user->location)) {{ $user->location }} @endif</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-4">
        <div class="d-flex">
          @if ($user->id == Auth::id() || FixometerHelper::hasRole(null, 'Administrator'))
            <a href="{{ url('/profile/edit/'.$user->id) }}" class="btn btn-primary ml-auto">Edit Profile</a>
          @endif
        </div>
      </div>
    </div>
    <br>
    <div class="row justify-content-center">
      <div class="col-sm-12 col-md-6">
        <div class="block">
          <h4>Biography</h4>
          <p>
              {{ $user->biography }}
          </p>
        </div>
      </div>
      <div class="col-sm-12 col-md-4">
        <div class="block">
          <h4>Repair skills</h4>
          <p>
            Cras mattis consectetur purus sit amet fermentum. Nulla vitae elit libero, a pharetra augue.
          </p>
          <ul class="nav flex-column">
            @if (isset($skills))
              @foreach ($skills as $skill)
                <li><a>{{ $skill }}</a></li>
              @endforeach
            @endif
          </ul>
        </div>
      </div>
    </div>
  </div>
@endsection
