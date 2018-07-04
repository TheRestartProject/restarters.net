@extends('layouts.app')
@section('content')
<section class="dashboard">
  <div class="container-fluid">
  <div class="row row-compressed">
    @if (!$has_profile_pic || !$has_skills || !$in_group || !$in_event)
    <div class="col-lg-3">
      <aside class="dashboard__aside">
        <ul class="steps">
          <li class="step step__active">
            <h3>Getting started</h3>
            <p>Welcome to restart, here are a few things to get you started:</p>
          </li>
          <li class="step">
            <div class="row">
              <div class="col-7 d-flex align-items-center">
                <h4>Add an avatar</h4>
              </div>
              @if ($has_profile_pic)
                <div class="col-5 d-flex align-items-center justify-content-end">
                  <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                </div>
              @else
                <div class="col-5 d-flex align-items-center justify-content-end">
                  <a href="/profile/edit/{{{ Auth::user()->id }}}#change-photo" class="step__link">Upload photo</a>
                </div>
              @endif
            </div>
          </li>
          <li class="step">
            <div class="row">
              <div class="col-7 d-flex align-items-center">
                <h4>Add your skills</h4>
              </div>
              @if ($has_skills)
                <div class="col-5 d-flex align-items-center justify-content-end">
                  <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                </div>
              @else
                <div class="col-5 d-flex align-items-center justify-content-end">
                  <a href="/profile/edit/{{{ Auth::user()->id }}}#repair-skills" class="step__link">Add Skills</a>
                </div>
              @endif
            </div>
          </li>
          @if (FixometerHelper::hasRole($user, 'Restarter'))

            <li class="step">
              <div class="row">
                <div class="col-7 d-flex align-items-center">
                  <h4>Join a Restart Group</h4>
                </div>
                @if ($in_group)
                  <div class="col-5 d-flex align-items-center justify-content-end">
                    <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                  </div>
                @else
                  <div class="col-5 d-flex align-items-center justify-content-end">
                    <a href="{{{ route('groups') }}}" class="step__link">Find a group</a>
                  </div>
                @endif
              </div>
            </li>
            <li class="step">
              <div class="row">
                <div class="col-7 d-flex align-items-center">
                  <h4>RSVP a event</h4>
                </div>
                @if ($in_event)
                  <div class="col-5 d-flex align-items-center justify-content-end">
                    <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                  </div>
                @else
                  <div class="col-5 d-flex align-items-center justify-content-end">
                    <a href="{{{ route('events') }}}" class="step__link">Find an event</a>
                  </div>
                @endif
              </div>
            </li>

          @endif

          @if (FixometerHelper::hasRole($user, 'Host'))

            <li class="step">
              <div class="row">
                <div class="col-7 d-flex align-items-center">
                  <h4>Create a Restart Group</h4>
                </div>
                @if ($in_group)
                  <div class="col-5 d-flex align-items-center justify-content-end">
                    <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                  </div>
                @else
                  <div class="col-5 d-flex align-items-center justify-content-end">
                    <a href="" class="step__link">Create a group</a>
                  </div>
                @endif
              </div>
            </li>
            <li class="step">
              <div class="row">
                <div class="col-7 d-flex align-items-center">
                  <h4>Create a Restart event</h4>
                </div>
                @if ($in_event)
                  <div class="col-5 d-flex align-items-center justify-content-end">
                    <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                  </div>
                @else
                  <div class="col-5 d-flex align-items-center justify-content-end">
                    <a href="" class="step__link">Create an event</a>
                  </div>
                @endif
              </div>
            </li>

          @endif

        </ul>
      </aside>
      @include('partials.impact')
    </div>
    @endif
    @if (!$has_profile_pic || !$has_skills || !$in_group || !$in_event)
      <div class="col-lg-9">
    @else
      <div class="col-lg-9 offset-lg-3">
    @endif
      <div class="row row-compressed">
        @if (FixometerHelper::hasRole($user, 'Administrator'))
          @include('dashboard.restarter')
        @endif
        @if (FixometerHelper::hasRole($user, 'Host'))
          @include('dashboard.host')
        @endif
        @if (FixometerHelper::hasRole($user, 'Restarter'))
          @include('dashboard.restarter')
        @endif
      </div>
    </div>
  </div>

  </div>
<section>
@endsection
