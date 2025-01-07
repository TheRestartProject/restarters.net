@extends('layouts.app', ['show_login_join_to_anons' => true])
@section('content')
  <section class="events">
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

      @if( is_object($is_attending) && !$event->hasFinished() )
        @if( $is_attending->status == 1 )
          <div class="alert alert-success">

            <div class="row">
              <div class="col-md-8 col-lg-8 d-flex flex-column align-content-center">@lang('events.rsvp_message')</div>
              <div class="col-md-4 col-lg-4 text-right">
                <a href="/party/cancel-invite/{{{ $is_attending->event }}}" class="btn btn-secondary">@lang('events.rsvp_button')</a>
              </div>
            </div>

          </div>
        @else
          <div class="alert alert-info">

            <div class="row">
              <div class="col-md-8 col-lg-9 d-flex flex-column align-content-center">@lang('events.pending_rsvp_message')</div>
              <div class="col-md-4 col-lg-3 d-flex flex-column align-content-center">
                <a href="/party/accept-invite/{{{ $is_attending->event }}}/{{{ $is_attending->status }}}" class="btn btn-primary">@lang('events.pending_rsvp_button')</a>
              </div>
            </div>

          </div>
        @endif
      @endif
      @if (\Session::has('prompt-follow-group'))
        <div class="alert alert-info" style="min-height: 88px;">
          <div class="row">
            <div class="col-md-8 col-lg-9 d-flex flex-column align-content-center">@lang('events.follow_hosting_group', ['group' => $event->theGroup->name])</div>
            <div class="col-md-4 col-lg-3 d-flex flex-column align-content-center">
              <a href="/group/join/{{ $event->theGroup->idgroups }}" class="btn btn-info">@lang('groups.join_group_button')</a>
            </div>
          </div>
        </div>
      @endif
      @if (\Session::has('now-following-group'))
        <div class="alert alert-success">
          <div class="row">
            <div class="col-md-8 col-lg-9 d-flex flex-column align-content-center">{{ \Session::get('now-following-group') }}</div>
          </div>
        </div>
      @endif

      <div>
        <div class="vue-placeholder vue-placeholder-large">
          <div class="vue-placeholder-content">@lang('partials.loading')...</div>
        </div>

        <?php
          $can_edit_event = App\Helpers\Fixometer::userHasEditPartyPermission($event->idevents);
          $can_delete_event = App\Helpers\Fixometer::userHasDeletePartyPermission($event->idevents) && $event->canDelete();
          $is_admin = Auth::check() && App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator');
          $is_attending = is_object($is_attending) && $is_attending->status == 1;

          // We need to expand a lot of event information to pass to the client.  In due course this will be replaced
          // by an API call to get the event details, and/or server-side rendering.
          $expanded_attended = $event->expandVolunteers($attended, $can_edit_event);
          $expanded_invited = $event->expandVolunteers($invited, $can_edit_event);
          $expanded_hosts = $event->expandVolunteers($hosts, $can_edit_event);

          $discourseThread = ($is_attending && $event->discourse_thread) ? (env('DISCOURSE_URL').'/t/'.$event->discourse_thread) : null;

          $collected_images = [];

          if (! empty($images)) {
              foreach ($images as $image) {
                  $collected_images[] = $image;
              }
          }

          $expanded_devices = [];

          foreach ($event->devices as $device) {
            $expanded_devices[] = (new \App\Http\Resources\Device($device))->resolve();
          }

          $expanded_clusters = [];

          foreach ($clusters as $cluster) {
              $cluster->categories;
              $expanded_clusters[] = $cluster;
          }

          $expanded_brands = [];

          foreach ($brands as $brand) {
              $brand->brand_name;
              $expanded_brands[] = $brand;
          }

          $expanded_event = \App\Http\Controllers\PartyController::expandEvent($event);
        ?>
        <div class="vue">
          <EventPage
            csrf="{{ csrf_token() }}"
            :idevents="{{ $event->idevents }}"
            :devices="{{ json_encode($expanded_devices, JSON_INVALID_UTF8_IGNORE) }}"
            :initial-event="{{ json_encode($expanded_event, JSON_INVALID_UTF8_IGNORE) }}"
            :is-attending="{{ $is_attending ? 'true' : 'false' }}"
            discourse-thread="{{ $discourseThread }}"
            :canedit="{{ $can_edit_event ? 'true' : 'false' }}"
            :candelete="{{ $can_delete_event ? 'true' : 'false' }}"
            :is-admin="{{ $is_admin ? 'true' : 'false' }}"
            :in-group="{{ Auth::user() && Auth::user()->isInGroup($event->theGroup->idgroups) ? 'true' : 'false' }}"
            :hosts="{{ json_encode($expanded_hosts, JSON_INVALID_UTF8_IGNORE) }}"
            :calendar-links="{{ json_encode($calendar_links != [] ? $calendar_links : null, JSON_INVALID_UTF8_IGNORE) }}"
            :attendance="{{ json_encode($expanded_attended, JSON_INVALID_UTF8_IGNORE) }}"
            :invitations="{{ json_encode($expanded_invited, JSON_INVALID_UTF8_IGNORE) }}"
            :images="{{ json_encode($collected_images, JSON_INVALID_UTF8_IGNORE)}}"
            :stats="{{ json_encode($stats, JSON_INVALID_UTF8_IGNORE) }}"
            :clusters="{{ json_encode($expanded_clusters, JSON_INVALID_UTF8_IGNORE) }}"
            :brands="{{ json_encode($expanded_brands, JSON_INVALID_UTF8_IGNORE) }}"
            :barrier-list="{{ json_encode(App\Helpers\Fixometer::allBarriers(), JSON_INVALID_UTF8_IGNORE) }}"
          />
        </div>
      </div>

      <div class="vue-placeholder vue-placeholder-large">
        <div class="vue-placeholder-content">@lang('partials.loading')...</div>
      </div>
    </div>
  </section>

  @include('includes.modals.event-invite-to')
  @include('includes.modals.event-description')
  @include('includes.modals.event-share-stats')
  @include('includes.modals.event-request-review')

@endsection
