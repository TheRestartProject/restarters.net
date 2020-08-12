@extends('layouts.app')
@section('content')
<section>
  <div class="container">
    <div class="row">
          <div class="col-12 col-md-12 mb-50">
              <div class="d-flex align-items-center">
                  <h1 class="mb-0 mr-30">
                      Profile & Preferences
                  </h1>

            @if (Auth::id() == $user->id)
              <a href="/profile" class="btn btn-primary ml-auto">View profile</a>
            @else
              <a href="/profile/{{ $user->id }}" class="btn btn-primary ml-auto">View user profile</a>
            @endif
      </div>
    </div>
    </div>

    @if(session()->has('message'))
      <div class="alert alert-success col-lg-12">
        {{ session()->get('message') }}
      </div>
    @endif

    @if (session()->has('error'))
      <div class="alert alert-danger col-lg-12">
        {{ session()->get('error') }}
      </div>
    @endif

    <div class="row justify-content-center">
      <div class="col-lg-4 offset-lg-sidebar">
        <div class="list-group" id="list-tab" role="tablist">
          <a class="list-group-item list-group-item-action active" id="list-profile-list" data-toggle="list" href="#list-profile" role="tab" aria-controls="profile">Profile</a>
          <a class="list-group-item list-group-item-action" id="list-account-list" data-toggle="list" href="#list-account" role="tab" aria-controls="account">Account</a>

          <a class="list-group-item list-group-item-action" id="list-email-preferences-list" data-toggle="list" href="#list-email-preferences" role="tab" aria-controls="email-preferences">Email preferences</a>
          <a class="list-group-item list-group-item-action" id="list-calendar-links-list" data-toggle="list" href="#list-calendar-links" role="tab" aria-controls="calendar-links">Calendars</a>
          @if (Auth::id() == $user->id)
              <a class="list-group-item list-group-item-action" id="list-notifications-list" href="{{ route('notifications') }}" role="tab">Notifications</a>
          @endif

        </div>
      </div>
      <div class="col-lg-8" aria-labelledby="list-profile-list">

        <div class="tab-content" id="nav-tabContent">

          <div class="tab-pane fade show active" id="list-profile" role="tabpanel" aria-labelledby="list-profile-list">
              @include('user.profile.profile')
          </div>

          <div class="tab-pane fade" id="list-account" role="tabpanel" aria-labelledby="list-account-list">
            @include('user.profile.account')
          </div>

          <div class="tab-pane fade" id="list-email-preferences" role="tabpanel" aria-labelledby="list-email-preferences-list">
              @include('user.profile.email-preferences')
          </div>

          <div class="tab-pane fade" id="list-calendar-links" role="tabpanel" aria-labelledby="list-calendar-links-list">
              @include('user.profile.calendars')
          </div>

        </div>

      </div>
    </div>
  </div>
</section>
@endsection
