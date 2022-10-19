@extends('layouts.app')

@section('title')
    Events
@endsection

@section('content')

<section class="events events-page">
  <div class="container">

      @if (\Session::has('success'))
          <div class="alert alert-success">
              {!! \Session::get('success') !!}
          </div>
      @endif
      @if (\Session::has('warning'))
          <div class="alert alert-warning">
              {!! \Session::get('warning') !!}
          </div>
      @endif

      @if( is_null($group) )
      <div class="row mb-30">
          <div class="col-12 col-md-12">
              <div class="d-flex align-items-center">
                  <h1 class="mb-0 mr-30">
                      @lang('events.events')
                  </h1>

                  <div class="mr-auto d-none d-md-block">
                      @include('svgs.fixometer.events-doodle')
                  </div>

                  @if( App\Helpers\Fixometer::userCanCreateEvents(Auth::user()) )
                      <a href="/party/create" class="btn btn-primary ml-auto">
                          <span class="d-none d-lg-block">@lang('events.add_event')</span>
                          <span class="d-block d-lg-none">@lang('events.create_new_event_mobile')</span>
                      </a>
                  @endif
              </div>
          </div>
      </div>
      @endif

    {{-- Events List --}}
    <div class="row justify-content-center">
      <div class="col-lg-12">
        {{-- Events to Moderate (Admin Only) --}}
        @if ( App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') || App\Helpers\Fixometer::hasRole(Auth::user(), 'NetworkCoordinator'))
        <div class="vue-placeholder vue-placeholder-large">
          <div class="vue-placeholder-content">@lang('partials.loading')...</div>
        </div>
        <div class="vue">
            <EventsRequiringModeration />
        </div>
        @endif
        {{-- END Events to Moderate (Admin Only) --}}

      <?php

      $can_edit_group = Auth::user() && $group && (App\Helpers\Fixometer::hasRole( Auth::user(), 'Administrator') || $isCoordinatorForGroup || $is_host_of_group);
      $showCalendar = Auth::check() && (!$group || ($group && $group->isVolunteer()) || App\Helpers\Fixometer::hasRole( Auth::user(), 'Administrator'));
      $calendar_copy_url = '';
      $calendar_edit_url = '';

      if ($showCalendar) {
          if ($group) {
              $calendar_copy_url = url("/calendar/group/{$group->idgroups}");
              $calendar_edit_url = url("/profile/edit/" . Auth::user()->id);
          } else {
              $calendar_copy_url = url("/calendar/user/" . Auth::user()->calendar_hash);
              $calendar_edit_url = url("/profile/edit/" . Auth::user()->id . "#list-calendar-links");
          }
      }

      ?>

    <div class="vue-placeholder vue-placeholder-large">
        <div class="vue-placeholder-content">@lang('partials.loading')...</div>
    </div>
      @if( is_null($group) )
      <div class="vue">
        <GroupEvents
            heading-level="h2"
            heading-sub-level="h3"
            :initial-events="{{ json_encode($expanded_events, JSON_INVALID_UTF8_IGNORE) }}"
            :add-group-name="true"
            calendar-copy-url="{{ $calendar_copy_url }}"
            calendar-edit-url="{{ $calendar_edit_url }}"
            :add-button="false"
            :canedit="{{ $can_edit_group ? 'true' : 'false' }}"
            add-group-name
            show-other
            location="{{ Auth::user()->location ?? '' }}"
        />
      </div>
      @else
      <div class="vue">
        <GroupEventsPage
          csrf="{{ csrf_token() }}"
          :idgroups="{{ $group ? $group->idgroups : 'null' }}"
          :events="{{ json_encode($expanded_events, JSON_INVALID_UTF8_IGNORE) }}"
          calendar-copy-url="{{ $showCalendar ? url("/calendar/group/{$group->idgroups}") : '' }}"
          calendar-edit-url="{{ $calendar_edit_url }}"
          :initial-group="{{ json_encode($group, JSON_INVALID_UTF8_IGNORE) }}"
          :canedit="{{ $can_edit_group ? 'true' : 'false' }}"
          location="{{ Auth::user()->location ?? '' }}"
        />
      </div>
      @endif
      </div>
    {{-- END Events List --}}



  </div>
</section>

@endsection
