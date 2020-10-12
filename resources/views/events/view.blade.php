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
                <a href="/party/accept-invite/{{{ $is_attending->event }}}/{{{ $is_attending->status }}}" class="btn btn-info">@lang('events.pending_rsvp_button')</a>
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
          // We need to expand a lot of event information to pass to the client.  In due course this will be replaced
          // by an API call to get the event details, and/or server-side rendering.
          function expandVolunteer($volunteers) {
            $ret = [];

            foreach ($volunteers as $volunteer) {
              $volunteer['volunteer'] = $volunteer->volunteer;
              $volunteer['userSkills'] = [];
              $volunteer['profilePath'] = '/uploads/thumbnail_placeholder.png';

              if (!empty($volunteer->volunteer)) {
                  $volunteer['userSkills'] = $volunteer->volunteer->userSkills->all();
                  $volunteer['profilePath'] = '/uploads/thumbnail_' . $volunteer->volunteer->getProfile($volunteer->volunteer->id)->path;
              }

              foreach ($volunteer['userSkills'] as $skill) {
                // Force expansion
                $skill->skillName->skill_name;
              }

              $volunteer['fullName'] = $volunteer->getFullName();
              $ret[] = $volunteer;
            }

            return $ret;
          }

          $expanded_attended = expandVolunteer($attended);
          $expanded_invited = expandVolunteer($invited);
          $expanded_hosts = expandVolunteer($hosts);

          // Trigger expansion of group.
          $group_image = $event->theGroup->groupImage;
          if (is_object($group_image) && is_object($group_image->image)) {
            $group_image->image->path;
          }

          $can_edit_event = ( Auth::check() && ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($event->idevents, Auth::user()->id) ) );
          $is_attending = is_object($is_attending) && $is_attending->status == 1;

          $collected_images = [];

          $stats = [];

          if ($event->isInProgress() || $event->hasFinished()) {
            $stats = $event->getEventStats((new App\Helpers\FootprintRatioCalculator())->calculateRatio());
          }

          if( !empty($images) ) {
              foreach ($images as $image) {
                $collected_images[] = $image;
              }
          }

          $expanded_devices = [];

          foreach ($event->devices as $device) {
            $device->category = $device->deviceCategory;
            $device->shortProblem = $device->getShortProblem();
            $device->urls;
            $device->images = $device->getImages();
            $expanded_devices[] = $device;
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
        ?>
        <div class="vue">
          <EventPage
                  :idevents="{{ $event->idevents }}"
                  :devices="{{ json_encode($expanded_devices) }}"
                  :initial-event="{{ json_encode($event) }}"
                  :is-attending="{{ $is_attending ? 'true' : 'false' }}"
                  :canedit="{{ $can_edit_event ? 'true' : 'false' }}"
                  :in-group="{{ Auth::user() && Auth::user()->isInGroup($event->theGroup->idgroups) ? 'true' : 'false' }}"
                  :hosts="{{ json_encode($expanded_hosts) }}"
                  :calendar-links="{{ json_encode($calendar_links != [] ? $calendar_links : null) }}"
                  :attendance="{{ json_encode($expanded_attended) }}"
                  :invitations="{{ json_encode($expanded_invited) }}"
                  :images="{{ json_encode($collected_images)}}"
                  :stats="{{ json_encode($stats) }}"
                  :clusters="{{ json_encode($expanded_clusters) }}"
                  :brands="{{ json_encode($expanded_brands) }}"
                  :barrier-list="{{ json_encode(FixometerHelper::allBarriers()) }}"
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
  @include('includes.modals.event-all-volunteers')
  @include('includes.modals.event-all-attended')
  @include('includes.modals.event-add-volunteer')
  @include('includes.modals.event-request-review')

@endsection
