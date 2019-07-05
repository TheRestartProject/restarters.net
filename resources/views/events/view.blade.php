@extends('layouts.app')
@section('content')
<section class="events">
  <div class="container-fluid">

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
        <div class="col-md-8 col-lg-9 d-flex flex-column align-content-center">@lang('events.rsvp_message')</div>
        <div class="col-md-4 col-lg-3 d-flex flex-column align-content-center">
          <a href="/party/cancel-invite/{{{ $is_attending->event }}}" class="btn btn-info">@lang('events.rsvp_button')</a>
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
          <h1>{{ $event->getEventName() }}</h1>
          <p>Organised by <a href="/group/view/{{ $formdata->group_id }}">{{ trim($formdata->group_name) }}</a></p>
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
                Event actions
              </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a href="{{ url('/') }}/party/edit/{{ $formdata->id, count($attended), count($invited) }}" class="dropdown-item">Edit event</a>
                @if( !$event->isInProgress() && !$event->hasFinished() )
                <form action="{{ url('/') }}/party/delete/{{ $formdata->id }}" method="post">
                  @csrf
                  <button id="deleteEvent" class="dropdown-item" data-party-id="{{$formdata->id}}" data-count-attended="{{count($attended)}}" data-count-invited="{{count($invited)}}" data-count-volunteers="{{$event->volunteers}}">Delete event</button>
                </form>
                @endif
                @if( $event->hasFinished() )
                  <button data-toggle="modal" data-target="#event-share-stats" class="btn dropdown-item">Share event stats</button>
                  <a href="#" class="btn dropdown-item" data-toggle="modal" data-target="#event-request-review">Request review</a>
                @else
                  @if( is_object($is_attending) && $is_attending->status == 1 && $event->isUpcoming() )
                  <button data-toggle="modal" data-target="#event-invite-to" class="btn dropdown-item">Invite volunteers</button>
                  @else
                  <a class="btn dropdown-item" href="/party/join/{{ $formdata->id }}">RSVP</a>
                  @endif
                @endif
                @if (! Auth::user()->isInGroup($event->theGroup->idgroups))
                    <a class="btn dropdown-item" href="/group/join/{{ $event->theGroup->idgroups }}">Follow group</a>
                @endif
              </div>
            </div>
            @else
                @if( $event->hasFinished() )
                    <button data-toggle="modal" data-target="#event-share-stats" class="btn btn-primary">Share event stats</a>
                @else
                    @if (! Auth::user()->isInGroup($event->theGroup->idgroups))
                        <a class="btn btn-tertiary" href="/group/join/{{ $event->theGroup->idgroups }}">Follow group</a>
                    @endif
                    @if( is_object($is_attending) && $is_attending->status == 1 && $event->isUpcoming() )
                        <button data-toggle="modal" data-target="#event-invite-to" class="btn btn-primary">Invite volunteers</button>
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

      <div class="row">
        <div class="col-lg-4">

          <aside class="sidebar-lg-offset">

            <h2>Event details</h2>
            <div class="card events-card">
              <div id="event-map" class="map" data-latitude="{{ $formdata->latitude }}" data-longitude="{{ $formdata->longitude }}" data-zoom="14"></div>

              <div class="events-card__details">

                <div class="row flex-row d-flex">

                  <div class="col-4 d-flex flex-column"><strong>Date/time: </strong></div>
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
                                aria-expanded="false">Add to calendar</a>

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

                  <div class="col-4 d-flex flex-column"><strong>Address: </strong></div>
                  <div class="col-8 d-flex flex-column"><address>{{ $formdata->location }}</address></div>

                  @if( count($hosts) > 0 )
                  <div class="col-4 d-flex flex-column"><strong>{{{ str_plural('Host', count($hosts) ) }}}: </strong></div>
                  <div class="col-8 d-flex flex-column">
                    @foreach( $hosts as $host )
                    {{ $host->volunteer->name }}<br>
                    @endforeach
                  </div>
                  @endif

                  @if( $event->isInProgress() || $event->hasFinished() )
                  <div class="col-4 col-label d-flex flex-column"><strong>Participants:</strong></div>
                  <div class="col-8 d-flex flex-column">
                    @if( Auth::check() )
                    @if( FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) || FixometerHelper::hasRole(Auth::user(), 'Administrator') )
                    <div>
                      <div class="input-group-qty">
                        <label for="participants_qty" class="sr-only">Quantity:</label>
                        <button class="increase btn-value">+</button>
                        <input name="participants_qty" id="participants_qty" maxlength="3" value="{{ $formdata->pax }}" title="Qty" class="input-text form-control qty" type="number">
                        <button class="decrease btn-value">–</button>
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

                    <div class="col-4 col-label d-flex flex-column"><strong>Volunteers:</strong></div>

                    <div class="col-8 d-flex flex-column">
                      @if( Auth::check() )
                      @if( FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) || FixometerHelper::hasRole(Auth::user(), 'Administrator') )
                      <div>
                        <div class="input-group-qty">
                          <label for="volunteer_qty" class="sr-only">Quantity:</label>
                          <button class="increaseVolunteers btn-value">+</button>
                          <input name="volunteer_qty" id="volunteer_qty" maxlength="3" value="{{ $event->volunteers }}" title="Qty" class="input-text form-control qty" type="number">
                          <button class="decreaseVolunteers btn-value">–</button>
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
            <h2 class="d-none d-lg-block">Event photos</h2>
            <h2 class="collapse-header"><a class="collapsed" data-toggle="collapse" href="#event-photos-section" role="button" aria-expanded="false" aria-control"event-photos-section">Event photos <span class="badge badge-pill badge-primary" id="photos-counter">1</span></a></h2>
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

          @if( $event->isInProgress() || $event->hasFinished() )
          <h2 id="environmental-impact" class="d-none d-lg-block">Environmental impact</h2>
          <h2 id="environmental-impact" class="collapse-header"><a class="collapsed" data-toggle="collapse" href="#environmental-impact-section" role="button" aria-expanded="false" aria-controls="environmental-impact-section">Environmental impact</a></h2>
          <div id="environmental-impact-section" class="collapse d-lg-block collapse-section">
            <ul class="properties">
              <li>
                <div>
                  <h3>Waste prevented</h3>
                  <span id="waste-insert">{{ number_format(round($stats['ewaste']), 0, '.', ',') }}</span> kg
                  <svg width="16" height="18" viewBox="0 0 13 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.15,0c0,0 -15.921,1.349 -11.313,10.348c0,0 0.59,-1.746 2.003,-3.457c0.852,-1.031 2,-2.143 3.463,-2.674c0.412,-0.149 0.696,0.435 0.094,0.727c0,0 -4.188,2.379 -4.732,6.112c0,0 1.805,1.462 3.519,1.384c1.714,-0.078 4.268,-1.078 4.707,-3.551c0.44,-2.472 1.245,-6.619 2.259,-8.889Z" style="fill:#0394a6;"/><path d="M1.147,13.369c0,0 0.157,-0.579 0.55,-2.427c0.394,-1.849 0.652,-0.132 0.652,-0.132l-0.25,2.576l-0.952,-0.017Z" style="fill:#0394a6;"/></g></svg>
                </div>
              </li>
              <li>
                <div>
                  <h3>CO<sub>2</sub> emissions prevented</h3>
                  <span id="co2-insert">{{ number_format(round($stats['co2']), 0, '.', ',') }}</span> kg
                  <svg width="20" height="12" viewBox="0 0 15 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><circle cx="2.854" cy="6.346" r="2.854" style="fill:#0394a6;"/><circle cx="11.721" cy="5.92" r="3.279" style="fill:#0394a6;"/><circle cx="7.121" cy="4.6" r="4.6" style="fill:#0394a6;"/><rect x="2.854" y="6.346" width="8.867" height="2.854" style="fill:#0394a6;"/></g></svg>
                </div>
              </li>
              <li>
                <div>
                  <h3>Fixed devices</h3>
                  <span id="fixed-insert">{{ $stats['fixed_devices'] }}</span>
                  <svg width="17" height="15" viewBox="0 0 14 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M6.601,1.38c1.344,-1.98 4.006,-1.564 5.351,-0.41c1.345,1.154 1.869,3.862 0,5.77c-1.607,1.639 -3.362,3.461 -5.379,4.615c-2.017,-1.154 -3.897,-3.028 -5.379,-4.615c-1.822,-1.953 -1.344,-4.616 0,-5.77c1.345,-1.154 4.062,-1.57 5.407,0.41Z" style="fill:#0394a6;"/></svg>
                </div>
              </li>
              <li>
                <div>
                  <h3>Repairable devices</h3>
                  <span id="repair-insert">{{ $stats['repairable_devices'] }}</span>
                  <svg width="20" height="20" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.33,7.915l1.213,1.212c0.609,0.61 0.609,1.599 0,2.208l-2.208,2.208c-0.609,0.609 -1.598,0.609 -2.208,0l-1.212,-1.213l4.415,-4.415Zm-9.018,-6.811c0.609,-0.609 1.598,-0.609 2.207,0l1.213,1.213l-4.415,4.415l-1.213,-1.213c-0.609,-0.609 -0.609,-1.598 0,-2.207l2.208,-2.208Z" style="fill:#0394a6;"/><path d="M11.406,1.027c-0.61,-0.609 -1.599,-0.609 -2.208,0l-8.171,8.171c-0.609,0.609 -0.609,1.598 0,2.208l2.208,2.207c0.609,0.61 1.598,0.61 2.208,0l8.17,-8.17c0.61,-0.61 0.61,-1.599 0,-2.208l-2.207,-2.208Zm-4.373,8.359c0.162,-0.163 0.425,-0.163 0.588,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.162 -0.426,0.162 -0.588,0c-0.163,-0.162 -0.163,-0.426 0,-0.588Zm1.176,-1.177c0.163,-0.162 0.426,-0.162 0.589,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.163 -0.426,0.163 -0.589,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm-2.359,-0.006c0.162,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.426,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm3.536,-1.17c0.162,-0.163 0.426,-0.163 0.588,0c0.162,0.162 0.162,0.425 0,0.588c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.588Zm-2.359,-0.007c0.162,-0.162 0.426,-0.162 0.588,0c0.162,0.163 0.162,0.426 0,0.589c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.589Zm-2.361,-0.006c0.163,-0.163 0.426,-0.163 0.589,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.162 -0.426,0.162 -0.589,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm3.537,-1.17c0.162,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.426,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm-2.36,-0.007c0.163,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.425,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm1.177,-1.177c0.162,-0.162 0.426,-0.162 0.588,0c0.162,0.163 0.162,0.426 0,0.589c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.589Z" style="fill:#0394a6;"/></g></svg>
                </div>
              </li>
              <li>
                <div>
                  <h3>Devices to be recycled</h3>
                  <span id="dead-insert">{{ $stats['dead_devices'] }}</span>
                  <svg width="20" height="20" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M2.382,10.651c-0.16,0.287 -0.287,0.719 -0.287,0.991c0,0.064 0,0.144 0.016,0.256l-1.999,-3.438c-0.064,-0.112 -0.112,-0.272 -0.112,-0.416c0,-0.145 0.048,-0.32 0.112,-0.432l0.959,-1.679l-1.071,-0.607l3.486,-0.065l1.695,3.054l-1.087,-0.623l-1.712,2.959Zm1.536,-9.691c0.303,-0.528 0.8,-0.816 1.407,-0.816c0.656,0 1.168,0.305 1.535,0.927l0.544,0.912l-1.887,3.263l-3.054,-1.775l1.455,-2.511Zm0.223,12.457c-0.911,0 -1.663,-0.752 -1.663,-1.663c0,-0.256 0.112,-0.688 0.272,-0.96l0.512,-0.911l3.79,0l0,3.534l-2.911,0l0,0Zm3.039,-12.553c-0.24,-0.415 -0.559,-0.704 -0.943,-0.864l3.933,0c0.352,0 0.624,0.144 0.784,0.417l0.976,1.662l1.055,-0.624l-1.696,3.039l-3.469,-0.049l1.071,-0.607l-1.711,-2.974Zm6.061,9.051c0.479,0 0.88,-0.128 1.215,-0.383l-1.983,3.453c-0.16,0.272 -0.447,0.432 -0.783,0.432l-1.872,0l0,1.231l-1.791,-2.99l1.791,-2.991l0,1.248l3.423,0l0,0Zm1.534,-2.879c0.145,0.256 0.225,0.528 0.225,0.816c0,0.576 -0.368,1.183 -0.879,1.471c-0.241,0.128 -0.577,0.209 -0.912,0.209l-1.056,0l-1.886,-3.263l3.054,-1.743l1.454,2.51Z" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg>
                </div>
              </li>
            </ul>
          </div>

          @endif

          @if( !empty($formdata->free_text) )
          <h2 id="description" class="d-none d-lg-block">Description</h2>
          <h2 id="description" class="collapse-header"><a class="collapsed" data-toggle="collapse" href="#description-section" role="button" aria-expanded="false" aria-controls="description-section">Description</a></h2>

          <div id="description-section" class="collapse d-lg-block collapse-section">
            <div class="events__description">
              {!! str_limit(strip_tags($formdata->free_text), 440, '...') !!}
              @if( strlen(strip_tags($formdata->free_text)) > 440 )
              <button data-toggle="modal" data-target="#event-description"><span>Read more</span></button>
              @endif
            </div>
          </div>
          @endif

          <h2 id="attendance" class="d-none d-lg-block">Attendance</h2>
          <h2 id="attendance" class="collapse-header"><a class="collapsed" data-toggle="collapse" href="#events-attendance-section" role="button" aria-expanded="false" aria-controls="events-attendance-section">Attendance <span class="badge badge-pill badge-primary" id="attended-counter">{{ count($attended) }}</span></a></h2>

          <div id="events-attendance-section" class="collapse d-lg-block collapse-section">
            <ul class="nav nav-tabs" id="events-attendance" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="attended-tab" data-toggle="tab" href="#attended" role="tab" aria-controls="attended" aria-selected="true"><svg width="16" height="18" viewBox="0 0 12 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="top: 3px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M9.268,3.161c-0.332,-0.212 -0.776,-0.119 -0.992,0.207c-0.216,0.326 -0.122,0.763 0.21,0.975c1.303,0.834 2.08,2.241 2.08,3.766c0,1.523 -0.777,2.93 -2.078,3.764c-0.001,0.001 -0.001,0.001 -0.002,0.001c-0.741,0.475 -1.601,0.725 -2.486,0.725c-0.885,0 -1.745,-0.25 -2.486,-0.725c-0.001,0 -0.001,0 -0.001,0c-1.302,-0.834 -2.08,-2.241 -2.08,-3.765c0,-1.525 0.778,-2.932 2.081,-3.766c0.332,-0.212 0.426,-0.649 0.21,-0.975c-0.216,-0.326 -0.66,-0.419 -0.992,-0.207c-1.711,1.095 -2.732,2.945 -2.732,4.948c0,2.003 1.021,3.852 2.732,4.947c0,0 0.001,0.001 0.002,0.001c0.973,0.623 2.103,0.952 3.266,0.952c1.164,0 2.294,-0.33 3.268,-0.953c1.711,-1.095 2.732,-2.944 2.732,-4.947c0,-2.003 -1.021,-3.853 -2.732,-4.948" style="fill:#0394a6;fill-rule:nonzero;"/><path d="M7.59,2.133c0.107,-0.36 -0.047,-1.227 -0.503,-1.758c-0.214,0.301 -0.335,0.688 -0.44,1.022c-0.182,0.066 -0.364,-0.014 -0.581,-0.082c-0.116,-0.037 -0.505,-0.121 -0.584,-0.245c-0.074,-0.116 0.073,-0.249 0.146,-0.388c0.051,-0.094 0.094,-0.231 0.136,-0.337c0.049,-0.126 0.07,-0.247 -0.006,-0.345c-0.462,0.034 -1.144,0.404 -1.394,0.906c-0.067,0.133 -0.101,0.393 -0.089,0.519c0.011,0.104 0.097,0.313 0.161,0.424c0.249,0.426 0.588,0.781 0.766,1.206c0.22,0.525 0.172,0.969 0.182,1.52c0.041,2.214 -0.006,2.923 -0.01,5.109c0,0.189 -0.014,0.415 0.031,0.507c0.26,0.527 1.029,0.579 1.29,-0.001c0.087,-0.191 0.028,-0.571 0.017,-0.843c-0.033,-0.868 -0.056,-1.708 -0.08,-2.526c-0.033,-1.142 -0.06,-0.901 -0.117,-1.97c-0.028,-0.529 -0.023,-1.117 0.275,-1.629c0.141,-0.24 0.657,-0.78 0.8,-1.089" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg> @if( $event->hasFinished() ) Attended @else Confirmed @endif <span class="badge badge-pill badge-primary" id="attended-counter">{{ count($attended) }}</span></a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="invited-tab" data-toggle="tab" href="#invited" role="tab" aria-controls="invited" aria-selected="false"><svg width="16" height="12" viewBox="0 0 12 9" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="top:0px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><g><ellipse cx="10.796" cy="1.139" rx="1.204" ry="1.139" style="fill:#0394a6;"/><ellipse cx="5.961" cy="4.5" rx="1.204" ry="1.139" style="fill:#0394a6;"/><ellipse cx="1.204" cy="1.139" rx="1.204" ry="1.139" style="fill:#0394a6;"/><path d="M10.796,0l-9.592,0l-0.753,2.031l4.823,3.409l0.687,0.199l0.643,-0.173l4.89,-3.397l-0.698,-2.069Z" style="fill:#0394a6;"/></g><path d="M12,2.59c0,-0.008 0,5.271 0,5.271c0,0.628 -0.539,1.139 -1.204,1.139c-0.052,0 -0.104,-0.003 -0.155,-0.009l-0.02,0.009l-9.417,0c-0.665,0 -1.204,-0.511 -1.204,-1.139c0,0 0,-4.602 0,-5.096c0,-0.028 0,-0.175 0,-0.175c0,0.004 0.176,0.329 0.452,0.538l-0.001,0.003l4.823,3.408l0.012,0.003c0.193,0.124 0.425,0.197 0.675,0.197c0.233,0 0.45,-0.063 0.634,-0.171l0.009,-0.002l0.045,-0.032c0.016,-0.01 0.031,-0.021 0.047,-0.032l4.798,-3.334l0,-0.001c0.306,-0.206 0.506,-0.568 0.506,-0.577Z" style="fill:#0394a6;"/></g></svg> Invited <span class="badge badge-pill badge-primary" id="invited-counter">{{ count($invited) }}</span></a>
              </li>
            </ul>
            <div class="tab-content" id="events-attendance-tabs">
              <div class="tab-pane fade show active" id="attended" role="tabpanel" aria-labelledby="attended-tab">
                <div class="users-list-wrap">
                  @if( count($attended) == 0 && !$event->hasFinished() )
                  <p class="text-center m-2">No volunteers have yet been confirmed for this event</p>
                  @elseif( count($attended) == 0 && $event->hasFinished() && ( FixometerHelper::hasRole(Auth::user(), 'Restarter') || Auth::guest() ) )
                  <p class="text-center m-2">No volunteers were confirmed for this event</p>
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
                  <a class="users-list__more" data-toggle="modal" data-target="#event-all-attended" href="#">See all @if( $event->hasFinished() ) attended @else confirmed @endif</a>
                  @endif
                  @endif
                </div>
              </div>
              <div class="tab-pane fade" id="invited" role="tabpanel" aria-labelledby="invited-tab">
                <div class="users-list-wrap">

                  @if( count($invited) == 0 && !$event->hasFinished() && ( FixometerHelper::hasRole(Auth::user(), 'Restarter') || Auth::guest() ) )
                  <p class="text-center m-2">No volunteers invites were recorded for this event</p>
                  @elseif( count($invited) == 0 && $event->hasFinished() )
                  <p class="text-center m-2">No volunteers invites were sent for this event</p>
                  @else
                  <ul class="users-list">
                    @foreach( $invited_summary as $volunteer )
                    @include('partials.volunteer-badge', ['type' => 'invited'])
                    @endforeach
                    @if( Auth::check() )
                    @if ( ( FixometerHelper::hasRole(Auth::user(), 'Host') || FixometerHelper::hasRole(Auth::user(), 'Administrator') ) && !$event->hasFinished() )
                    <li class="users-list__invite">
                      <button data-toggle="modal" data-target="#event-invite-to">Invite to join event</button>
                    </li>
                    @endif
                    @endif
                  </ul>
                  @if( count($invited) > 0 )
                  <a class="users-list__more" data-toggle="modal" data-target="#event-all-volunteers" href="#">See all invited</a>
                  @endif
                  @endif
                </div>
              </div>
            </div>
          </div>

        </div>

        @if( $event->isInProgress() || $event->hasFinished() )

        <div class="col-lg-12">

          <h2 id="devices" class="d-none d-lg-block"><svg width="20" height="18" viewBox="0 0 15 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="position:relative;z-index:1;top:-3px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M13.528,13.426l-12.056,0c-0.812,0 -1.472,-0.66 -1.472,-1.472l0,-7.933c0,-0.812 0.66,-1.472 1.472,-1.472l4.686,0l-1.426,-2.035c-0.059,-0.086 -0.039,-0.203 0.047,-0.263l0.309,-0.217c0.086,-0.06 0.204,-0.039 0.263,0.047l1.729,2.468l0.925,0l1.728,-2.468c0.06,-0.086 0.178,-0.107 0.263,-0.047l0.31,0.217c0.085,0.06 0.106,0.177 0.046,0.263l-1.425,2.035l4.601,0c0.812,0 1.472,0.66 1.472,1.472l0,7.933c0,0.812 -0.66,1.472 -1.472,1.472Zm-4.012,-9.499l-7.043,0c-0.607,0 -1.099,0.492 -1.099,1.099l0,5.923c0,0.607 0.492,1.099 1.099,1.099l7.043,0c0.606,0 1.099,-0.492 1.099,-1.099l0,-5.923c0,-0.607 -0.493,-1.099 -1.099,-1.099Zm3.439,3.248c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Zm0,-2.819c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Z" style="fill:#0394a6;"/></svg> Devices <span class="badge badge-pill badge-primary">{{ count($formdata->devices) }}</span></h2>
          <h2 id="devices" class="collapse-header"><a class="collapsed" data-toggle="collapse" href="#devices-section" role="button" aria-expanded="false" aria-controls="devices-section">Devices <span class="badge badge-pill badge-primary">{{ count($formdata->devices) }}</span></a></h2>

          <div id="devices-section" class="collapse d-lg-block collapse-section">

            <div class="table-responsive">
              <table class="table table-repair" role="table" id="device-table">
                <thead>
                  <tr>
                    <th width="60"></th>
                    <th class="text-center"><svg width="22" height="17" viewBox="0 0 17 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="position:relative;z-index:1;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Camera</title><path d="M8.25,4.781c-1.367,0 -2.475,1.071 -2.475,2.391c0,1.32 1.108,2.39 2.475,2.39c1.367,0 2.475,-1.07 2.475,-2.39c0,-1.32 -1.108,-2.391 -2.475,-2.391Zm6.6,-2.39l-1.98,0c-0.272,0 -0.566,-0.204 -0.652,-0.454l-0.511,-1.484c-0.087,-0.249 -0.38,-0.453 -0.652,-0.453l-5.61,0c-0.272,0 -0.566,0.204 -0.652,0.454l-0.511,1.483c-0.087,0.25 -0.38,0.454 -0.652,0.454l-1.98,0c-0.908,0 -1.65,0.717 -1.65,1.593l0,7.172c0,0.877 0.742,1.594 1.65,1.594l13.2,0c0.907,0 1.65,-0.717 1.65,-1.594l0,-7.172c0,-0.876 -0.743,-1.593 -1.65,-1.593Zm-6.6,8.765c-2.278,0 -4.125,-1.784 -4.125,-3.984c0,-2.2 1.847,-3.985 4.125,-3.985c2.278,0 4.125,1.785 4.125,3.985c0,2.2 -1.847,3.984 -4.125,3.984Zm6.022,-6.057c-0.318,0 -0.577,-0.25 -0.577,-0.558c0,-0.308 0.259,-0.558 0.577,-0.558c0.32,0 0.578,0.25 0.578,0.558c0,0.308 -0.259,0.558 -0.578,0.558Z" style="fill:#0394a6;fill-rule:nonzero;"/></svg></th>
                    <th class="d-none d-md-table-cell">Category</th>
                    <th class="d-none d-md-table-cell">Brand</th>
                    <th class="d-none d-md-table-cell">Model</th>
                    <th class="d-none d-md-table-cell">Age</th>
                    <th><span class="d-none d-sm-inline">Description of problem/solution</span></th>
                    <th width="65px">Status</th>
                    <th width="95px">Spare parts</th>
                    @if( Auth::check() )
                    @if(FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) )
                    <th width="35px" class="d-none d-md-table-cell"></th>
                    @endif
                    @endif
                  </tr>
                </thead>
                <tbody>
                  @foreach($event->devices as $device)
                  @include('partials.tables.row-device')
                  @endforeach
                </tbody>
              </table>
            </div>

            @include('partials.event-add-device')

          </div>

        </div>
        @endif
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
