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
        // We need to expand the user objects to pass to the client.  In due course this will be replaced
        // by an API call to get the event details.
          function expandVolunteer($volunteers) {
            $ret = [];

            foreach ($volunteers as $volunteer) {
              $volunteer['volunteer'] = $volunteer->volunteer;
              $volunteer['userSkills'] = $volunteer->volunteer->userSkills->all();

              foreach ($volunteer['userSkills'] as $skill) {
                // Force expansion
                $skill->skillName->skill_name;
        }

              $volunteer['fullName'] = $volunteer->getFullName();
              $volunteer['profilePath'] = '/uploads/thumbnail_' . $volunteer->volunteer->getProfile($volunteer->volunteer->id)->path;
              $ret[] = $volunteer;
        }

            return $ret;
          }

          $expanded_attended = expandVolunteer($attended);
          $expanded_invited = expandVolunteer($invited);
          $expanded_hosts = expandVolunteer($hosts);

        // Trigger expansion of group.
        $group_image = $event->theGroup->groupImage;
        if (is_object($group_image->image)) {
          $group_image->image->path;
        }

        $can_edit_event = (FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id)) || FixometerHelper::hasRole(Auth::user(), 'Administrator');
        $is_attending = is_object($is_attending) && $is_attending->status == 1;
        ?>

        <div class="vue">
          <EventHeading :event-id="{{ $event->idevents }}" :event="{{ $event }}" :is-attending="{{ $is_attending ? 'true' : 'false' }}" :canedit="{{ $can_edit_event ? 'true' : 'false' }}":in-group="{{ Auth::user() && Auth::user()->isInGroup($event->theGroup->idgroups) ? 'true' : 'false' }}" />
        </div>

        <div class="d-flex flex-wrap">
          <div class="w-xs-100 w-md-50">
            <div class="vue">
                <EventDetails class="pr-md-3" :event-id="{{ $event->idevents }}" :event="{{ $event }}" :hosts="{{ json_encode($expanded_hosts) }}" :calendar-links="{{ json_encode($calendar_links != [] ? $calendar_links : null) }}" />
            </div>
            <div class="vue">
              <EventDescription class="pr-md-3" :event-id="{{ $event->idevents }}" :event="{{ $event }}" />
            </div>
          </div>
          <div class="w-xs-100 w-md-50 vue">
            <EventAttendance class="pl-md-3" :event-id="{{ $event->idevents }}" :event="{{ $event }}" :attendance="{{ json_encode($expanded_attended) }}" :invitations="{{ json_encode($expanded_invited) }}" :canedit="{{ $can_edit_event ? 'true' : 'false' }}" />
          </div>
        </div>
        @if( !empty($images) )
          <?php
            $collected_images = [];
            foreach ($images as $image) {
              $collected_images[] = $image;
            }
          ?>
          <div class="vue">
            <EventImages :images="{{ json_encode($collected_images)}}" />
          </div>
        @endif
      </div>

      <div class="vue-placeholder vue-placeholder-large">
        <div class="vue-placeholder-content">@lang('partials.loading')...</div>
      </div>

      @if( $event->isInProgress() || $event->hasFinished() )
        <div class="vue w-100">
          <EventStats :stats="{{ json_encode($event->getEventStats((new App\Helpers\FootprintRatioCalculator())->calculateRatio())) }}" />
      </div>
      @endif

      @if( $event->isInProgress() || $event->hasFinished() )

        <div class="col-lg-12 p-sm-0">
          <h2 id="devices" class="d-none d-lg-block"><svg width="20" height="18" viewBox="0 0 15 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="position:relative;z-index:1;top:-3px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M13.528,13.426l-12.056,0c-0.812,0 -1.472,-0.66 -1.472,-1.472l0,-7.933c0,-0.812 0.66,-1.472 1.472,-1.472l4.686,0l-1.426,-2.035c-0.059,-0.086 -0.039,-0.203 0.047,-0.263l0.309,-0.217c0.086,-0.06 0.204,-0.039 0.263,0.047l1.729,2.468l0.925,0l1.728,-2.468c0.06,-0.086 0.178,-0.107 0.263,-0.047l0.31,0.217c0.085,0.06 0.106,0.177 0.046,0.263l-1.425,2.035l4.601,0c0.812,0 1.472,0.66 1.472,1.472l0,7.933c0,0.812 -0.66,1.472 -1.472,1.472Zm-4.012,-9.499l-7.043,0c-0.607,0 -1.099,0.492 -1.099,1.099l0,5.923c0,0.607 0.492,1.099 1.099,1.099l7.043,0c0.606,0 1.099,-0.492 1.099,-1.099l0,-5.923c0,-0.607 -0.493,-1.099 -1.099,-1.099Zm3.439,3.248c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Zm0,-2.819c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Z" style="fill:#0394a6;"/></svg> @lang('devices.title_items_at_event') <span id="devices-total" class="badge badge-pill badge-primary">{{ $stats['devices_powered'] + $stats['devices_unpowered'] }}</span></h2>
          <h2 id="devices" class="collapse-header"><a class="collapsed" data-toggle="collapse" href="#devices-section" role="button" aria-expanded="false" aria-controls="devices-section"><b>@lang('devices.title_items_at_event')</b> <span class="font-weight-light">({{ $stats['devices_powered'] + $stats['devices_unpowered'] }})</span></a></h2>

          <div id="devices-section" class="collapse d-lg-block collapse-section p-0 ourtabs ourtabs-brand">
            <ul class="nav nav-tabs d-flex" id="myTab" role="tablist">
              <li class="nav-item flex-grow-1 active">
                <a class="nav-link active" id="items-powered-tab" data-toggle="tab" href="#items-powered" role="tab" aria-controls="items-powered" aria-selected="true"><b>@lang('devices.title_powered')</b> <span id="devices-powered">({{ $stats['devices_powered'] }})</span></a>
              </li>
              <li class="nav-item flex-grow-1">
                <a class="nav-link" id="items-unpowered-tab" data-toggle="tab" href="#items-unpowered" role="tab" aria-controls="items-unpowered"><b>@lang('devices.title_unpowered')</b> <span id="devices-unpowered">({{ $stats['devices_unpowered'] }})</span></a>
              </li>
            </ul>
            <div class="tab-content" id="itemsTabContent">
              <div class="tab-pane fade show active" id="items-powered" role="tabpanel" aria-labelledby="items-powered-tab">
                <p class="mt-3">@lang('devices.description_powered')</p>
                @include('partials.device-list', [
                    'powered' => TRUE,
                    'event_id' => $event->idevents
                ])
                  @if( Auth::check() && ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($event->idevents, Auth::user()->id) ) )
                  <a class="collapsed row-button" id="open-add-powered" data-toggle="collapse" href="#add-edit-device-powered-" role="button" aria-expanded="false" aria-controls="add-edit-device-powered-">
                    <button class="btn btn-primary text-center mb-4 ml-4 align-bottom" type="button"><img style="width:20px;height:20px" class="mb-1" src="/images/add-icon.svg" /> @lang('partials.add_device_powered')</button>
                  </a>
                  @include('fixometer.device-add-or-edit', [
                      'device' => new \App\Device(),
                      'powered' => TRUE,
                      'add' => TRUE,
                      'edit' => FALSE
                  ])
                @endif
              </div>
              <div class="tab-pane fade" id="items-unpowered" role="tabpanel" aria-labelledby="items-unpowered-tab">
                <p class="mt-3">@lang('devices.description_unpowered')</p>
                @include('partials.device-list', [
                    'powered' => FALSE
                ])
                  @if( Auth::check() && ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($event->idevents, Auth::user()->id) ) )
                  <a class="collapsed row-button" id="open-add-unpowered" data-toggle="collapse" href="#add-edit-device-unpowered-" role="button" aria-expanded="false" aria-controls="add-edit-device-unpowered-">
                    <button class="btn btn-primary text-center mb-4 ml-4 align-bottom" type="button"><img style="width:20px;height:20px" class="mb-1" src="/images/add-icon.svg" /> @lang('partials.add_device_unpowered')</button>
                  </a>
                  @include('fixometer.device-add-or-edit', [
                      'device' => new \App\Device(),
                      'powered' => FALSE,
                      'add' => TRUE,
                      'edit' => FALSE
                  ])
                @endif
              </div>
            </div>
          </div>
        </div>
      @endif
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
