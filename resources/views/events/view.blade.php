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

      <div class="events__header row align-content-top">
        <div class="col-lg-8 d-flex flex-column">

          <header>
            <h1>{{ $event->getEventName() }}@if ($event->online) <span class="badge badge-info">@lang('events.online_event')</span>@endif</h1>
            <p>@lang('events.organised_by', ['group' => '<a href="/group/view/'. $formdata->group_id .'">'. trim($formdata->group_name) .'</a>'])</p>
            {{--
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{{ route('dashboard') }}}">FIXOMETER</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/group') }}">@lang('groups.groups')</a></li>
                <li class="breadcrumb-item"><a href="/group/view/{{ $formdata->group_id }}">{{ trim($formdata->group_name) }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $event->getEventName() }}</li>
              </ol>
            </nav>
            --}}
            @php( $group_image = $event->theGroup->groupImage )
            @if( is_object($group_image) && is_object($group_image->image) )
              <img src="{{ asset('/uploads/mid_' . $group_image->image->path) }}" alt="{{{ $event->theGroup->name }}}" class="event-icon">
            @else
              <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $event->host->name }}}" class="event-icon">
            @endif
          </header>

        </div>
        <div class="col-lg-4">
          <div class="button-group button-group__r">
            @if( Auth::check() )
              @if( FixometerHelper::userHasEditPartyPermission($formdata->id) || FixometerHelper::userIsHostOfGroup($formdata->group_id, Auth::user()->id) )
                <div class="dropdown">
                  <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    @lang('events.event_actions')
                  </button>
                  <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a href="{{ url('/') }}/party/edit/{{ $formdata->id, count($attended), count($invited) }}" class="dropdown-item">@lang('events.edit_event')</a>
                    @if( !$event->isInProgress() && !$event->hasFinished() )
                      <form action="{{ url('/') }}/party/delete/{{ $formdata->id }}" method="post">
                        @csrf
                        <button id="deleteEvent" class="dropdown-item" data-party-id="{{$formdata->id}}" data-count-attended="{{count($attended)}}" data-count-invited="{{count($invited)}}" data-count-volunteers="{{$event->volunteers}}">@lang('events.delete_event')</button>
                      </form>
                    @endif
                    @if( $event->hasFinished() )
                      <a href="#" class="btn dropdown-item" data-toggle="modal" data-target="#event-request-review">@lang('events.request_review')</a>
                      <button data-toggle="modal" data-target="#event-share-stats" class="btn dropdown-item">@lang('events.share_event_stats')</button>
                    @else
                      @if( is_object($is_attending) && $is_attending->status == 1 && $event->isUpcoming() )
                        <button data-toggle="modal" data-target="#event-invite-to" class="btn dropdown-item">@lang('events.invite_volunteers')</button>
                      @else
                        <a class="btn dropdown-item" href="/party/join/{{ $formdata->id }}">RSVP</a>
                      @endif
                    @endif
                    @if (! Auth::user()->isInGroup($event->theGroup->idgroups))
                      <a class="btn dropdown-item" href="/group/join/{{ $event->theGroup->idgroups }}">@lang('events.follow_group')</a>
                    @endif
                  </div>
                </div>
              @else
                @if( $event->hasFinished() )
                  <a data-toggle="modal" data-target="#event-share-stats" class="btn btn-primary">@lang('events.share_event_stats')</a>
                @else
                  @if (! Auth::user()->isInGroup($event->theGroup->idgroups))
                    <a class="btn btn-tertiary" href="/group/join/{{ $event->theGroup->idgroups }}">@lang('events.follow_group')</a>
                  @endif
                  @if( is_object($is_attending) && $is_attending->status == 1 && $event->isUpcoming() )
                    <button data-toggle="modal" data-target="#event-invite-to" class="btn btn-primary">@lang('events.invite_volunteers')</button>
                  @else
                    <a class="btn btn-primary" href="/party/join/{{ $formdata->id }}">RSVP</a>
                  @endif
                @endif
              @endif
            @endif
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-12">
          <p></p>
          <p></p>
        </div>
      </div>

      <!-- So far only upcoming events have been moved over to Vue. -->
      @if($event->isUpcoming())
        <div>
          <div class="vue-placeholder vue-placeholder-large">
            <div class="vue-placeholder-content">@lang('partials.loading')...</div>
          </div>

          <?php
          // We need to expand the user objects to pass to the client.  In due course this will be replaced
          // by an API call to get the event details.
          $expanded_attended = [];
          foreach ($attended as $att) {
            $thisone = $att;
            $thisone['volunteer'] = $att->volunteer;
            $thisone['userSkills'] = $att->volunteer->userSkills;
            $thisone['fullName'] = $att->getFullName();
            $thisone['profilePath'] = $att->volunteer->getProfile($att->id)->path;
            $expanded_attended[] = $thisone;
          }
          $expanded_invited = [];
          foreach ($invited as $att) {
            $thisone = $att;
            $thisone['volunteer'] = $att->volunteer;
            $thisone['userSkills'] = $att->volunteer->userSkills;
            $thisone['fullName'] = $att->getFullName();
            $thisone['profilePath'] = $att->volunteer->getProfile($att->id)->path;
            $expanded_invited[] = $thisone;
          }

          $expanded_hosts = [];
          foreach ($hosts as $host) {
            $thisone = $host;
            $thisone['volunteer'] = $host->volunteer;
            $expanded_hosts[] = $thisone;
          }

          $attendance_edit = (FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id)) || FixometerHelper::hasRole(Auth::user(), 'Administrator');
          ?>

          <div class="d-flex flex-wrap">
            <div class="w-xs-100 w-md-50">
              <div class="vue">
                <EventDetails class="pr-md-3" :event-id="{{ $event->idevents }}" :event="{{ $event }}" :hosts="{{ json_encode($expanded_hosts) }}" :calendar-links="{{ json_encode($calendar_links) }}" />
              </div>
              <div class="vue">
                <hr class="d-block d-md-none">
                <EventDescription class="pr-md-3" :event-id="{{ $event->idevents }}" :event="{{ $event }}" />
              </div>
            </div>
            <div class="w-xs-100 w-md-50 vue">
              <EventAttendance class="pl-md-3" :event-id="{{ $event->idevents }}" :event="{{ $event }}" :attendance="{{ json_encode($expanded_attended) }}" :invitations="{{ json_encode($expanded_invited) }}" :canedit="{{ $attendance_edit ? 'true' : 'false' }}" />
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

          @if( $event->isInProgress() || $event->hasFinished() )
            <div class="vue w-100">
              <EventStats class="ml-2 mr-2" :stats="{{ json_encode($event->getEventStats((new App\Helpers\FootprintRatioCalculator())->calculateRatio())) }}" />
            </div>
          @endif

        </div>
      @else
          <div class="row">
            <div class="col-lg-4">

            <aside id="event-details" class="sidebar-lg-offset">

              <h2>@lang('events.event_details')</h2>
              <div class="card events-card">
                @if ( ! $event->online )
                  <div id="event-map" class="map" data-latitude="{{ $formdata->latitude }}" data-longitude="{{ $formdata->longitude }}" data-zoom="14"></div>
                @endif

                <div class="events-card__details">

                  <div class="row flex-row d-flex">

                    <div class="col-4 d-flex flex-column"><strong>@lang('events.date_time'): </strong></div>
                    <div class="col-8 d-flex flex-column">
                      {{ date('D jS M Y', $formdata->event_date) }}<br>
                      {{ $event->getEventStartEnd() }}
                      @if( $event->isUpcoming() && ! empty($calendar_links) )
                        <div class="dropdown dropdown-calendar">
                          <a
                                  class="btn btn-link dropdown-toggle"
                                  href="#"
                                  role="button"
                                  id="addToCalendar"
                                  data-toggle="dropdown"
                                  aria-haspopup="true"
                                  aria-expanded="false">@lang('events.add_to_calendar')</a>

                          <div class="dropdown-menu" aria-labelledby="addToCalendar">
                            <span class="dropdown-menu-arrow"></span>
                            <a target="_blank" class="dropdown-item" href="{{{ $calendar_links['google'] }}}">Google Calendar</a>
                            <a target="_blank" class="dropdown-item" href="{{{ $calendar_links['webOutlook'] }}}">Outlook</a>
                            <a target="_blank" class="dropdown-item" href="{{{ $calendar_links['ics'] }}}">iCal</a>
                            <a target="_blank" class="dropdown-item" href="{{{ $calendar_links['yahoo'] }}}">Yahoo Calendar</a>
                          </div>
                        </div>
                      @endif
                    </div>

                    @if ( ! $event->online )
                      <div class="col-4 d-flex flex-column"><strong>@lang('events.event_address'): </strong></div>

                      <div class="col-8 d-flex flex-column"><address>{{ $formdata->location }}</address></div>
                    @endif

                    @if( count($hosts) > 0 )
                      <div class="col-4 d-flex flex-column"><strong>{{{ str_plural('Host', count($hosts) ) }}}: </strong></div>
                      <div class="col-8 d-flex flex-column">
                        @foreach( $hosts as $host )
                          {{ $host->volunteer->name }}<br>
                        @endforeach
                      </div>
                    @endif

                    @if( $event->isInProgress() || $event->hasFinished() )
                      <div class="col-4 col-label d-flex flex-column"><strong>@lang('events.participants'):</strong></div>
                      <div class="col-8 d-flex flex-column">
                        @if( Auth::check() )
                          @if( FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) || FixometerHelper::hasRole(Auth::user(), 'Administrator') )
                            <div>
                              <div class="input-group-qty">
                                <label for="participants_qty" class="sr-only">@lang('events.quantity'):</label>
                                <button class="decrease btn-value">–</button>
                                <input name="participants_qty" id="participants_qty" maxlength="3" value="{{ $formdata->pax }}" title="Qty" class="input-text form-control qty" type="number">
                                <button class="increase btn-value">+</button>
                              </div>
                            </div>
                          @else
                            {{ $formdata->pax }}
                          @endif
                        @else
                          {{ $formdata->pax }}
                        @endif

                      </div>
                    @endif

                    @if( $event->isInProgress() || $event->hasFinished() )

                      <div class="col-4 col-label d-flex flex-column"><strong>@lang('events.volunteers'):</strong></div>

                      <div class="col-8 d-flex flex-column">
                        @if( Auth::check() )
                          @if( FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) || FixometerHelper::hasRole(Auth::user(), 'Administrator') )
                            <div>
                              <div class="input-group-qty">
                                <label for="volunteer_qty" class="sr-only">@lang('events.quantity'):</label>
                                <button class="decreaseVolunteers btn-value">–</button>
                                <input name="volunteer_qty" id="volunteer_qty" maxlength="3" value="{{ $event->volunteers }}" title="Qty" class="input-text form-control qty" type="number">
                                <button class="increaseVolunteers btn-value">+</button>
                              </div>
                            </div>
                          @else
                            {{ $event->volunteers }}
                          @endif
                        @else
                          {{ $event->volunteers }}
                        @endif

                      </div>

                      <div class="col-12 invalid-feedback" id="warning_volunteers_message" style="display: none;">
                        @lang('events.warning_volunteers_message')
                      </div>

                    @endif

                  </div>

                </div>

              </div>
              @if( !empty($images) )
                <h2 class="d-none d-lg-block">@lang('events.event_photos')</h2>
                <h2 class="collapse-header"><a class="collapsed" data-toggle="collapse" href="#event-photos-section" role="button" aria-expanded="false" aria-control"event-photos-section">@lang('events.event_photos') <span class="badge badge-pill badge-primary" id="photos-counter">1</span></a></h2>
                <div id="event-photos-section" class="collapse d-lg-block collapse-section">
                  <ul class="photo-list">
                    @foreach($images as $image)
                      <li>
                        <a href="/uploads/{{ $image->path }}" data-toggle="lightbox">
                          <img src="/uploads/thumbnail_{{ $image->path }}" alt="placeholder" width="100">
                        </a>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif

            </aside>
        </div>
            <div class="col-lg-8">

          @if( !empty($formdata->free_text) )
            <h2 id="description" class="d-none d-lg-block">@lang('events.event_description')</h2>
            <h2 id="description" class="collapse-header"><a class="collapsed" data-toggle="collapse" href="#description-section" role="button" aria-expanded="false" aria-controls="description-section">@lang('events.event_description')</a></h2>

            <div id="description-section" class="collapse d-lg-block collapse-section">
              <div class="events__description">
                {!! str_limit(strip_tags($formdata->free_text), 440, '...') !!}
                @if( strlen(strip_tags($formdata->free_text)) > 440 )
                  <button data-toggle="modal" data-target="#event-description"><span>Read more</span></button>
                @endif
              </div>
            </div>
          @endif

          <h2 id="attendance" class="d-none d-lg-block">@lang('events.event_attendance')</h2>
          <h2 id="attendance" class="collapse-header"><a class="collapsed" data-toggle="collapse" href="#events-attendance-section" role="button" aria-expanded="false" aria-controls="events-attendance-section">@lang('events.event_attendance') <span class="badge badge-pill badge-primary" id="attended-counter">{{ count($attended) }}</span></a></h2>

          <div id="events-attendance-section" class="collapse d-lg-block collapse-section">
            <ul class="nav nav-tabs" id="events-attendance" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="attended-tab" data-toggle="tab" href="#attended" role="tab" aria-controls="attended" aria-selected="true"><svg width="16" height="18" viewBox="0 0 12 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="top: 3px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M9.268,3.161c-0.332,-0.212 -0.776,-0.119 -0.992,0.207c-0.216,0.326 -0.122,0.763 0.21,0.975c1.303,0.834 2.08,2.241 2.08,3.766c0,1.523 -0.777,2.93 -2.078,3.764c-0.001,0.001 -0.001,0.001 -0.002,0.001c-0.741,0.475 -1.601,0.725 -2.486,0.725c-0.885,0 -1.745,-0.25 -2.486,-0.725c-0.001,0 -0.001,0 -0.001,0c-1.302,-0.834 -2.08,-2.241 -2.08,-3.765c0,-1.525 0.778,-2.932 2.081,-3.766c0.332,-0.212 0.426,-0.649 0.21,-0.975c-0.216,-0.326 -0.66,-0.419 -0.992,-0.207c-1.711,1.095 -2.732,2.945 -2.732,4.948c0,2.003 1.021,3.852 2.732,4.947c0,0 0.001,0.001 0.002,0.001c0.973,0.623 2.103,0.952 3.266,0.952c1.164,0 2.294,-0.33 3.268,-0.953c1.711,-1.095 2.732,-2.944 2.732,-4.947c0,-2.003 -1.021,-3.853 -2.732,-4.948" style="fill:#0394a6;fill-rule:nonzero;"/><path d="M7.59,2.133c0.107,-0.36 -0.047,-1.227 -0.503,-1.758c-0.214,0.301 -0.335,0.688 -0.44,1.022c-0.182,0.066 -0.364,-0.014 -0.581,-0.082c-0.116,-0.037 -0.505,-0.121 -0.584,-0.245c-0.074,-0.116 0.073,-0.249 0.146,-0.388c0.051,-0.094 0.094,-0.231 0.136,-0.337c0.049,-0.126 0.07,-0.247 -0.006,-0.345c-0.462,0.034 -1.144,0.404 -1.394,0.906c-0.067,0.133 -0.101,0.393 -0.089,0.519c0.011,0.104 0.097,0.313 0.161,0.424c0.249,0.426 0.588,0.781 0.766,1.206c0.22,0.525 0.172,0.969 0.182,1.52c0.041,2.214 -0.006,2.923 -0.01,5.109c0,0.189 -0.014,0.415 0.031,0.507c0.26,0.527 1.029,0.579 1.29,-0.001c0.087,-0.191 0.028,-0.571 0.017,-0.843c-0.033,-0.868 -0.056,-1.708 -0.08,-2.526c-0.033,-1.142 -0.06,-0.901 -0.117,-1.97c-0.028,-0.529 -0.023,-1.117 0.275,-1.629c0.141,-0.24 0.657,-0.78 0.8,-1.089" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg> @if( $event->hasFinished() ) @lang('events.attended') @else @lang('events.confirmed') @endif <span class="badge badge-pill badge-primary" id="attended-counter">{{ count($attended) }}</span></a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="invited-tab" data-toggle="tab" href="#invited" role="tab" aria-controls="invited" aria-selected="false"><svg width="16" height="12" viewBox="0 0 12 9" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="top:0px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><g><ellipse cx="10.796" cy="1.139" rx="1.204" ry="1.139" style="fill:#0394a6;"/><ellipse cx="5.961" cy="4.5" rx="1.204" ry="1.139" style="fill:#0394a6;"/><ellipse cx="1.204" cy="1.139" rx="1.204" ry="1.139" style="fill:#0394a6;"/><path d="M10.796,0l-9.592,0l-0.753,2.031l4.823,3.409l0.687,0.199l0.643,-0.173l4.89,-3.397l-0.698,-2.069Z" style="fill:#0394a6;"/></g><path d="M12,2.59c0,-0.008 0,5.271 0,5.271c0,0.628 -0.539,1.139 -1.204,1.139c-0.052,0 -0.104,-0.003 -0.155,-0.009l-0.02,0.009l-9.417,0c-0.665,0 -1.204,-0.511 -1.204,-1.139c0,0 0,-4.602 0,-5.096c0,-0.028 0,-0.175 0,-0.175c0,0.004 0.176,0.329 0.452,0.538l-0.001,0.003l4.823,3.408l0.012,0.003c0.193,0.124 0.425,0.197 0.675,0.197c0.233,0 0.45,-0.063 0.634,-0.171l0.009,-0.002l0.045,-0.032c0.016,-0.01 0.031,-0.021 0.047,-0.032l4.798,-3.334l0,-0.001c0.306,-0.206 0.506,-0.568 0.506,-0.577Z" style="fill:#0394a6;"/></g></svg> @lang('events.invited') <span class="badge badge-pill badge-primary" id="invited-counter">{{ count($invited) }}</span></a>
              </li>
            </ul>
            <div class="tab-content" id="events-attendance-tabs">
              <div class="tab-pane fade show active" id="attended" role="tabpanel" aria-labelledby="attended-tab">
                <div class="users-list-wrap">
                  @if( count($attended) == 0 && !$event->hasFinished() )
                    <p class="text-center m-2">@lang('events.no_volunteers_yet_confirmed')</p>
                  @elseif( count($attended) == 0 && $event->hasFinished() && ( FixometerHelper::hasRole(Auth::user(), 'Restarter') || Auth::guest() ) )
                    <p class="text-center m-2">@lang('events.no_volunteers_were_confirmed')</p>
                  @else
                    <ul class="users-list">
                      @foreach( $attended_summary as $volunteer )
                        @include('partials.volunteer-badge', ['type' => 'attended'])
                      @endforeach

                      @if( Auth::check() )
                        @if ( ( FixometerHelper::hasRole(Auth::user(), 'Host') || FixometerHelper::hasRole(Auth::user(), 'Administrator') ) && $event->hasFinished() )
                          <li class="users-list__invite">
                            <button data-toggle="modal" data-target="#event-add-volunteer">Add volunteer</button>
                          </li>
                        @endif
                      @endif
                    </ul>
                    @if( count($attended) > 0 )
                      <a class="users-list__more" data-toggle="modal" data-target="#event-all-attended" href="#">@if( $event->hasFinished() ) @lang('events.see_all_attended') @else @lang('events.see_all_confirmed') @endif</a>
                    @endif
                  @endif
                </div>
              </div>
              <div class="tab-pane fade" id="invited" role="tabpanel" aria-labelledby="invited-tab">
                <div class="users-list-wrap">

                  @if( count($invited) == 0 && !$event->hasFinished() && ( FixometerHelper::hasRole(Auth::user(), 'Restarter') || Auth::guest() ) )
                    <p class="text-center m-2">@lang('events.no_invites_yet')</p>
                  @elseif( count($invited) == 0 && $event->hasFinished() )
                    <p class="text-center m-2">@lang('events.no_invites')</p>
                  @else
                    <ul class="users-list">
                      @foreach( $invited_summary as $volunteer )
                        @include('partials.volunteer-badge', ['type' => 'invited'])
                      @endforeach
                      @if( Auth::check() )
                        @if ( ( FixometerHelper::hasRole(Auth::user(), 'Host') || FixometerHelper::hasRole(Auth::user(), 'Administrator') ) && !$event->hasFinished() )
                          <li class="users-list__invite">
                            <button data-toggle="modal" data-target="#event-invite-to">@lang('events.invite_to_join')</button>
                          </li>
                        @endif
                      @endif
                    </ul>
                    @if( count($invited) > 0 )
                      <a class="users-list__more" data-toggle="modal" data-target="#event-all-volunteers" href="#">@lang('events.see_all_invited')</a>
                    @endif
                  @endif
                </div>
              </div>
            </div>
          </div>

        </div>
        @endif

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
                  @if( Auth::check() && ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($device->event, Auth::user()->id) ) )
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
                  @if( Auth::check() && ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($device->event, Auth::user()->id) ) )
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
