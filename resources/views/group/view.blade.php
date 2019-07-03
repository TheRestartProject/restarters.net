@extends('layouts.app')
@section('content')
<section class="events group-view">
  <div class="container-fluid">

      <?php if( isset($_GET['message']) && $_GET['message'] == 'invite' ): ?>
        <div class="alert alert-info" role="alert">
          Thank you, your invitation has been sent
        </div>
      <?php endif; ?>

      @if(session()->has('response'))
        @php( FixometerHelper::printResponse(session('response')) )
      @endif

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

      @if ($has_pending_invite)
          <div class="alert alert-info">
              You have an invitation to this group.  Please click 'Join Group' if you would like to join.
          </div>
      @endif

      <div class="events__header row align-content-top">
          <div class="col-lg-7 d-flex flex-column">

            <header>

                @if( FixometerHelper::hasRole( $user, 'Administrator' ) || ( $is_host_of_group && $user_groups > 1 ) )

                  <h1 class="sr-only">{{{ $group->name }}}</h1>
                  <button class="btn btn-title dropdown-toggle" type="button" id="dropdownTitle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      {{{ $group->name }}}
                  </button>
                  <div class="dropdown-menu dropdown-menu__titles" aria-labelledby="dropdownTitle">

                    @if( FixometerHelper::hasRole( $user, 'Administrator' ) )

                      @foreach($grouplist as $g)
                        <a class="dropdown-item" href="{{ url('/group/view') }}/{{ $g->id }}">
                          @if(!empty($g->path))
                            <img src="{{ url('/uploads/thumbnail_'.$g->path) }}" alt="{{ $g->name }} group image" class="dropdown-item-icon">
                          @else
                            <img src="{{ url('/images/placeholder_small.jpg') }}" alt="{{ $g->name }} group image" class="dropdown-item-icon">
                          @endif
                          <span>{{ $g->name }}</span>
                        </a>
                      @endforeach

                    @else

                      @foreach($userGroups as $g)
                        <a class="dropdown-item" href="{{ url('/group/view/'.$g->idgroups) }}" title="Switch to {{ $g->name }}">
                          @if(!empty($g->path))
                            <img src="{{ url('/uploads/mid_'.$g->path) }}" alt="{{ $g->name }} group image" class="dropdown-item-icon">
                          @else
                            <img src="{{ url('/images/placeholder_small.jpg') }}" alt="{{ $g->name }} group image" class="dropdown-item-icon">
                          @endif
                          <span>{{ $g->name }}</span>
                        </a>
                      @endforeach

                    @endif

                  </div>
                @else
                  <h1>{{{ $group->name }}}</h1>
                @endif

                <p>{{{ $group->location }}}</p>

                @if( !empty($group->website) )
                  <a class="events__header__url" href="{{{ $group->website }}}" rel="noopener noreferrer">{{{ $group->website }}}</a>
                @endif

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">FIXOMETER</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/group') }}">@lang('groups.groups')</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{{ $group->name }}}</li>
                    </ol>
                </nav>

                @php( $groupImage = $group->groupImage )
                @if( is_object($groupImage) && is_object($groupImage->image) )
                  <img src="{{ asset('/uploads/mid_'. $groupImage->image->path) }}" alt="{{{ $group->name }}} group image" class="event-icon">
                @else
                  <img src="{{ url('/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg') }}" alt="{{{ $group->name }}} group image" class="event-icon">
                @endif

            </header>



          </div>
          <div class="col-lg-5">

            @if( FixometerHelper::hasRole( $user, 'Administrator' ) || $is_host_of_group )
              <div class="button-group button-group__r">

                  <div class="dropdown">
                      <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          Group actions
                      </button>
                      <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a class="dropdown-item" href="{{ url('/group/edit/'.$group->idgroups) }}">Edit group</a>
                          <a class="dropdown-item" href="{{ url('/party/create') }}/{{ $group->idgroups }}">Add event</a>
                          <a class="dropdown-item" data-toggle="modal" data-target="#invite-to-group" href="#">Invite volunteers</a>
                          <a class="dropdown-item" href="{{ url('/group/nearby/'.$group->idgroups) }}#">Volunteers nearby</a>
                          <a class="dropdown-item" href="#" data-toggle="modal" data-target="#group-share-stats">Share group stats</a>
                      </div>
                  </div>

              </div>
            @else
              <div class="button-group button-group__r">
                  @if ($in_group)
                    <a class="btn btn-primary" href="#" data-toggle="modal" data-target="#invite-to-group">Invite volunteers</a>
                  @else
                    <a class="btn btn-primary" href="/group/join/{{ $group->idgroups }}" id="join-group">@lang('groups.join_group_button')</a>
                  @endif
                  <a class="btn btn-primary" href="#" data-toggle="modal" data-target="#group-share-stats">Share group stats</a>
              </div>
            @endif

          </div>
      </div>

      @include('partials.information-alert', [
        'html_text' => "<strong class='mb-2'>Did you know </strong> <br> You can now access all events using your personal calendar via an iCal feed? Find out more.",
        'dismissable_id' => "group-{$group->idgroups}",
        'classes' => ['set-information-box-margin'],
      ])

        <div class="row">
            <div class="col-lg-3">

                <h2 id="about-grp">About the group
                  @if( FixometerHelper::hasRole( $user, 'Administrator' ) || $is_host_of_group )
                    <sup>(<a href="{{ url('/group/edit/'.$group->idgroups) }}">Edit group</a>)</sup>
                  @endif
                </h2>

                <div class="events__description">
                    <h3 class="events__side__heading" id="description">Description:</h3>
                    <p>{!! str_limit(strip_tags($group->free_text), 160, '...') !!}</p>
                    @if( strlen($group->free_text) > 160 )
                      <button data-toggle="modal" data-target="#group-description"><span>Read more</span></button>
                    @endif
                </div><!-- /events__description -->


                @if ($in_group)
                  <h2 id="volunteers">Volunteers <sup>(<a data-toggle="modal" data-target="#invite-to-group" href="#">Invite to group</a>)</sup></h2>
                @else
                  <h2 id="volunteers">Volunteers <sup>(<a href="/group/join/{{ $group->idgroups }}">@lang('groups.join_group_button')</a>)</sup></h2>
                @endif

                <div class="tab">

                    <div class="users-list-wrap users-list__single">
                        <ul class="users-list">

                            @foreach( $view_group->allConfirmedVolunteers->take(3) as $volunteer )
                              @include('partials.volunteer-badge')
                            @endforeach

                        </ul>
                        @if( $view_group->allConfirmedVolunteers->count() > 3 )
                          <a class="users-list__more" href="#" data-toggle="modal" data-target="#group-volunteers">See all {{{ $view_group->allConfirmedVolunteers->count() }}} volunteers</a>
                        @endif
                    </div>

                </div>

            </div>
            <div class="col-lg-9">

                <h2 id="key-stats">Key stats</h2>
                <ul class="properties">
                    <li class="properties__item__full_xs">
                        <div class="h-100">
                          <div class="row h-100">
                            <div class="col-6 col-lg-12 d-flex align-content-center flex-column">
                              <h3>Participants</h3>
                              <svg width="18" height="18" viewBox="0 0 14 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M8.147,2.06c0.624,0.413 1.062,1.113 1.141,1.925c0.255,0.125 0.537,0.197 0.837,0.197c1.093,0 1.98,-0.936 1.98,-2.091c0,-1.155 -0.887,-2.091 -1.98,-2.091c-1.083,0 -1.962,0.92 -1.978,2.06Zm-1.297,4.282c1.093,0 1.98,-0.937 1.98,-2.092c0,-1.155 -0.887,-2.091 -1.98,-2.091c-1.094,0 -1.981,0.937 -1.981,2.091c0,1.155 0.887,2.092 1.981,2.092Zm0.839,0.142l-1.68,0c-1.397,0 -2.535,1.951 -2.535,3.428l0,2.92l0.006,0.034l0.141,0.047c1.334,0.44 2.493,0.587 3.447,0.587c1.863,0 2.943,-0.561 3.01,-0.597l0.132,-0.071l0.014,0l0,-2.92c0,-1.477 -1.137,-3.428 -2.535,-3.428Zm3.276,-1.937l-1.667,0c-0.018,0.704 -0.303,1.117 -0.753,1.573c1.242,0.391 2.152,2.358 2.152,3.795l0,0.669c1.646,-0.064 2.594,-0.557 2.657,-0.59l0.132,-0.07l0.014,0l0,-2.921c0,-1.477 -1.137,-2.456 -2.535,-2.456Zm-7.59,-0.364c0.388,0 0.748,-0.12 1.053,-0.323c0.097,-0.669 0.437,-1.253 0.921,-1.651c0.002,-0.039 0.006,-0.078 0.006,-0.117c0,-1.155 -0.887,-2.091 -1.98,-2.091c-1.093,0 -1.98,0.936 -1.98,2.091c0,1.154 0.887,2.091 1.98,2.091Zm1.779,1.937c-0.449,-0.454 -0.732,-0.863 -0.753,-1.563c-0.062,-0.005 -0.123,-0.01 -0.186,-0.01l-1.68,0c-1.398,0 -2.535,0.979 -2.535,2.456l0,2.92l0.005,0.034l0.142,0.047c1.07,0.353 2.025,0.515 2.855,0.567l0,-0.656c0,-1.437 0.909,-3.404 2.152,-3.795Z" style="fill:#0394a6;fill-rule:nonzero;"/></svg>
                              </div>
                              <div class="col-6 col-lg-12 d-flex align-items-end">
                                {{{ number_format($pax, 0, '.' , ',') }}}
                              </div>
                            </div>
                        </div>
                    </li>
                    <li class="properties__item__full_xs">
                        <div class="h-100">
                          <div class="row h-100">
                            <div class="col-6 col-lg-12 d-flex align-content-center flex-column">
                            <h3>Hours volunteered</h3>
                            <svg width="17" height="20" viewBox="0 0 12 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M9.268,3.161c-0.332,-0.212 -0.776,-0.119 -0.992,0.207c-0.216,0.326 -0.122,0.763 0.21,0.975c1.303,0.834 2.08,2.241 2.08,3.766c0,1.523 -0.777,2.93 -2.078,3.764c-0.001,0.001 -0.001,0.001 -0.002,0.001c-0.741,0.475 -1.601,0.725 -2.486,0.725c-0.885,0 -1.745,-0.25 -2.486,-0.725c-0.001,0 -0.001,0 -0.001,0c-1.302,-0.834 -2.08,-2.241 -2.08,-3.765c0,-1.525 0.778,-2.932 2.081,-3.766c0.332,-0.212 0.426,-0.649 0.21,-0.975c-0.216,-0.326 -0.66,-0.419 -0.992,-0.207c-1.711,1.095 -2.732,2.945 -2.732,4.948c0,2.003 1.021,3.852 2.732,4.947c0,0 0.001,0.001 0.002,0.001c0.973,0.623 2.103,0.952 3.266,0.952c1.164,0 2.294,-0.33 3.268,-0.953c1.711,-1.095 2.732,-2.944 2.732,-4.947c0,-2.003 -1.021,-3.853 -2.732,-4.948" style="fill:#0394a6;fill-rule:nonzero;"/><path d="M7.59,2.133c0.107,-0.36 -0.047,-1.227 -0.503,-1.758c-0.214,0.301 -0.335,0.688 -0.44,1.022c-0.182,0.066 -0.364,-0.014 -0.581,-0.082c-0.116,-0.037 -0.505,-0.121 -0.584,-0.245c-0.074,-0.116 0.073,-0.249 0.146,-0.388c0.051,-0.094 0.094,-0.231 0.136,-0.337c0.049,-0.126 0.07,-0.247 -0.006,-0.345c-0.462,0.034 -1.144,0.404 -1.394,0.906c-0.067,0.133 -0.101,0.393 -0.089,0.519c0.011,0.104 0.097,0.313 0.161,0.424c0.249,0.426 0.588,0.781 0.766,1.206c0.22,0.525 0.172,0.969 0.182,1.52c0.041,2.214 -0.006,2.923 -0.01,5.109c0,0.189 -0.014,0.415 0.031,0.507c0.26,0.527 1.029,0.579 1.29,-0.001c0.087,-0.191 0.028,-0.571 0.017,-0.843c-0.033,-0.868 -0.056,-1.708 -0.08,-2.526c-0.033,-1.142 -0.06,-0.901 -0.117,-1.97c-0.028,-0.529 -0.023,-1.117 0.275,-1.629c0.141,-0.24 0.657,-0.78 0.8,-1.089" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg>
                            </div>
                            <div class="col-6 col-lg-12 d-flex align-items-end">
                              {{{ number_format($hours, 0, '.' , ',') }}}
                            </div>
                          </div>
                        </div>
                    </li>
                    <li class="properties__item__full_xs">
                        <div class="h-100">
                          <div class="row h-100">
                            <div class="col-6 col-lg-12 d-flex align-content-center flex-column">
                              <h3>Total events</h3>
                              <svg width="18" height="18" viewBox="0 0 14 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M12.462,13.5l-11.423,0c-0.282,0 -0.525,-0.106 -0.731,-0.318c-0.205,-0.212 -0.308,-0.463 -0.308,-0.753l0,-9.215c0,-0.29 0.103,-0.541 0.308,-0.753c0.206,-0.212 0.449,-0.318 0.731,-0.318l1.038,0l0,-0.804c0,-0.368 0.127,-0.683 0.381,-0.945c0.255,-0.263 0.56,-0.394 0.917,-0.394l0.519,0c0.357,0 0.663,0.131 0.917,0.394c0.254,0.262 0.382,0.577 0.382,0.945l0,0.804l3.115,0l0,-0.804c0,-0.368 0.127,-0.683 0.381,-0.945c0.254,-0.263 0.56,-0.394 0.917,-0.394l0.519,0c0.357,0 0.663,0.131 0.917,0.393c0.254,0.263 0.381,0.578 0.381,0.946l0,0.804l1.039,0c0.281,0 0.525,0.106 0.73,0.318c0.205,0.212 0.308,0.463 0.308,0.753l0,9.215c0,0.29 -0.103,0.541 -0.308,0.753c-0.206,0.212 -0.449,0.318 -0.73,0.318Zm-0.087,-3.805l-2.25,0l0,1.909l2.25,0l0,-1.909Zm-6,0l-2.25,0l0,1.909l2.25,0l0,-1.909Zm-3,0l-2.25,0l0,1.909l2.25,0l0,-1.909Zm6,0l-2.25,0l0,1.909l2.25,0l0,-1.909Zm3,-2.658l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-3,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm3,-2.658l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-3,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-5.481,-3.307l-0.519,0c-0.07,0 -0.131,0.026 -0.182,0.079c-0.052,0.053 -0.077,0.116 -0.077,0.188l0,1.661c0,0.073 0.025,0.135 0.077,0.188c0.051,0.053 0.112,0.08 0.182,0.08l0.519,0c0.071,0 0.131,-0.027 0.183,-0.08c0.051,-0.053 0.077,-0.115 0.077,-0.188l0,-1.661c0,-0.072 -0.026,-0.135 -0.077,-0.188c-0.051,-0.053 -0.112,-0.079 -0.183,-0.079Zm6.231,0l-0.519,0c-0.07,0 -0.131,0.026 -0.183,0.079c-0.051,0.053 -0.077,0.116 -0.077,0.188l0,1.661c0,0.073 0.026,0.135 0.077,0.188c0.052,0.053 0.113,0.08 0.183,0.08l0.519,0c0.071,0 0.131,-0.027 0.183,-0.08c0.051,-0.053 0.077,-0.115 0.077,-0.188l0,-1.661c0,-0.072 -0.026,-0.135 -0.077,-0.188c-0.052,-0.053 -0.112,-0.079 -0.183,-0.079Z" style="fill:#0394a6;fill-rule:nonzero;"/></svg>
                            </div>
                            <div class="col-6 col-lg-12 d-flex align-items-end">
                              {{{ number_format(count($allparties), 0, '.' , ',') }}}
                            </div>
                          </div>
                        </div>
                    </li>
                    <li class="properties__item__full_xs">
                        <div class="h-100">
                            <div class="row h-100">
                              <div class="col-6 col-lg-12 d-flex align-content-center flex-column">
                                <h3>Waste prevented</h3>
                                <svg width="17" height="17" viewBox="0 0 13 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.15,0c0,0 -15.921,1.349 -11.313,10.348c0,0 0.59,-1.746 2.003,-3.457c0.852,-1.031 2,-2.143 3.463,-2.674c0.412,-0.149 0.696,0.435 0.094,0.727c0,0 -4.188,2.379 -4.732,6.112c0,0 1.805,1.462 3.519,1.384c1.714,-0.078 4.268,-1.078 4.707,-3.551c0.44,-2.472 1.245,-6.619 2.259,-8.889Z" style="fill:#0394a6;"/><path d="M1.147,13.369c0,0 0.157,-0.579 0.55,-2.427c0.394,-1.849 0.652,-0.132 0.652,-0.132l-0.25,2.576l-0.952,-0.017Z" style="fill:#0394a6;"/></g></svg>
                              </div>
                                                                                                                                                                                                                                 <div class="col-6 col-lg-12 d-flex align-items-end">
                                {{{ number_format(round($groupWaste), 0, '.' , ',') }}} kg
                              </div>
                          </div>
                        </div>
                    </li>
                    <li class="properties__item__full_xs">
                        <div class="h-100">
                          <div class="row h-100">
                            <div class="col-6 col-lg-12 d-flex align-content-center flex-column">
                              <h3>CO<sub>2</sub> emissions prevented</h3>
                              <svg width="20" height="12" viewBox="0 0 15 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><circle cx="2.854" cy="6.346" r="2.854" style="fill:#0394a6;"/><circle cx="11.721" cy="5.92" r="3.279" style="fill:#0394a6;"/><circle cx="7.121" cy="4.6" r="4.6" style="fill:#0394a6;"/><rect x="2.854" y="6.346" width="8.867" height="2.854" style="fill:#0394a6;"/></g></svg>
                            </div>
                                                                                                                                                                                                                               <div class="col-6 col-lg-12 d-flex align-items-end">
                              {{{ number_format(round($groupCo2), 0, '.' , ',') }}} kg
                            </div>
                          </div>
                        </div>
                    </li>
                </ul>

                <h2 id="upcoming-grp">Group events
                  @if ( Auth::check() && $group->isVolunteer() )
                    @php( $copy_link = url("/calendar/group/{$group->idgroups}") )
                    @php( $user_edit_link = url("/profile/edit/{$user->id}") )
                    @include('partials.calendar-feed-button', [
                      'copy_link' => $copy_link,
                      'user_edit_link' => $user_edit_link,
                    ])
                  @endif
                @if( FixometerHelper::hasRole( $user, 'Administrator' ) || FixometerHelper::hasRole( $user, 'Host' ) )<sup>(<a href="{{ url('/party/create') }}">Add event</a>)</sup>@endif</h2>

                <ul class="nav nav-tabs" id="myTab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" id="upcoming-past-tab" data-toggle="tab" href="#upcoming-past" role="tab" aria-controls="upcoming-past" aria-selected="true">Upcoming &amp; Active</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="past-tab" data-toggle="tab" href="#past" role="tab" aria-controls="past" aria-selected="false">Past</a>
                  </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                  <div class="tab-pane fade show active" id="upcoming-past" role="tabpanel" aria-labelledby="upcoming-past-tab">

                    <div class="events-list-wrap">
                      <div class="table-responsive">
                          <table class="table table-events table-striped" role="table">

                              @include('partials.tables.head-events', ['invite' => true, 'group_view' => true])

                              <tbody>

                                @if( !$upcoming_events->isEmpty() )
                                  @foreach ($upcoming_events as $event)
                                    @include('partials.tables.row-events', ['invite' => true, 'group_view' => true])
                                  @endforeach
                                @else
                                  <tr>
                                    <td colspan="13" align="center" class="p-3">There are currently no upcoming events for this group</td>
                                  </tr>
                                @endif

                              </tbody>
                          </table>
                      </div>
                    </div>

                  </div>
                  <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                    <div class="events-list-wrap">
                      <div class="table-responsive">
                          <table class="table table-events table-striped" role="table">

                              @include('partials.tables.head-events', ['group_view' => true, 'hide_invite' => true])

                              <tbody>

                                @if( !$past_events->isEmpty() )
                                  @foreach ($past_events as $event)
                                    @include('partials.tables.row-events', ['group_view' => true, 'hide_invite' => true])
                                  @endforeach
                                @else
                                  <tr>
                                    <td colspan="13" align="center" class="p-3">There are currently no past events for this group</td>
                                  </tr>
                                @endif

                              </tbody>
                          </table>
                      </div>
                    </div>
                  </div>

                  <div class="events-link-wrap text-center">
                    <a href="/party/group/{{{ $group->idgroups }}}">See all events</a>
                  </div>
                </div>

                <br>

                <h2 id="environmental-impact">Environmental impact</h2>

                <div class="row row-compressed-xs">
                    <div class="col-lg-3 d-flex flex-column">
                        <ul class="properties">
                            <li class="properties__item__full properties__item__half_xs">
                                <div>
                                <h3>Waste prevented</h3>
                                @php( $sum = 0 )
                                @foreach($waste_year_data as $y)
                                    @php( $sum += $y->waste )
                                @endforeach
                                {{{  number_format(round($groupWaste), 0, '.' , ',') }}} kg
                                <svg width="17" height="17" viewBox="0 0 13 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.15,0c0,0 -15.921,1.349 -11.313,10.348c0,0 0.59,-1.746 2.003,-3.457c0.852,-1.031 2,-2.143 3.463,-2.674c0.412,-0.149 0.696,0.435 0.094,0.727c0,0 -4.188,2.379 -4.732,6.112c0,0 1.805,1.462 3.519,1.384c1.714,-0.078 4.268,-1.078 4.707,-3.551c0.44,-2.472 1.245,-6.619 2.259,-8.889Z" style="fill:#0394a6;"/><path d="M1.147,13.369c0,0 0.157,-0.579 0.55,-2.427c0.394,-1.849 0.652,-0.132 0.652,-0.132l-0.25,2.576l-0.952,-0.017Z" style="fill:#0394a6;"/></g></svg>
                                </div>
                            </li>
                            <li class="properties__item__full properties__item__half_xs">
                                <div>
                                    <h3>CO<sub>2</sub> emissions prevented</h3>
                                    @php( $sum = 0 )
                                    @foreach($year_data as $y)
                                        @php( $sum += $y->co2 )
                                    @endforeach
                                    {{{ number_format(round($groupCo2), 0, '.' , ',') }}} kg
                                    <svg width="20" height="12" viewBox="0 0 15 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><circle cx="2.854" cy="6.346" r="2.854" style="fill:#0394a6;"/><circle cx="11.721" cy="5.92" r="3.279" style="fill:#0394a6;"/><circle cx="7.121" cy="4.6" r="4.6" style="fill:#0394a6;"/><rect x="2.854" y="6.346" width="8.867" height="2.854" style="fill:#0394a6;"/></g></svg>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <?php
                    /** find size of needed SVGs **/
                    if($sum >= 3000) {
                        $consume_svg = 'svg-car1';
                        $consume_label = 'Equal to driving';
                        $consume_eql_to = (1 / 0.12) * $groupCo2;
                        $consume_legend = number_format(round($consume_eql_to), 0, '.', ',') . ' km';

                        $manufacture_svg = 'svg-car2';
                        $manufacture_label = 'Like manufacturing';
                        $manufacture_eql_to = round($groupCo2 / 6000);
                        $manufacture_legend = $manufacture_eql_to . ' ' . str_plural('car', $manufacture_eql_to);
                    }
                    else {
                        $consume_svg = 'svg-tv';
                        $consume_label = 'Like watching TV for';
                        $consume_eql_to = ((1 / 0.024) * $groupCo2) / 24;
                        $consume_eql_to = number_format(round($consume_eql_to), 0, '.', ',');
                        $consume_legend = $consume_eql_to . ' ' . str_plural('day', $consume_eql_to);

                        $manufacture_svg = 'svg-sofa';
                        $manufacture_label = 'Like manufacturing';
                        $manufacture_eql_to = round($groupCo2 / 100);
                        $manufacture_legend = $manufacture_eql_to . ' ' . str_plural('sofa', $manufacture_eql_to);
                    }
                    ?>

                    <div class="col-lg-9 d-flex flex-column">
                        <div class="row row-compressed-xs">
                            <div class="col-lg-6 d-flex flex-column">
                                <div class="stat">
                                    <h3>{{{ $consume_label }}}</h3>
                                    @include('partials/'.$consume_svg)
                                    <p>{{{ $consume_legend }}}</p>
                                </div>
                            </div>
                            <div class="col-lg-6 d-flex flex-column">
                                <div class="stat">
                                    <h3>{{{ $manufacture_label }}}</h3>
                                    @include('partials/'.$manufacture_svg)
                                    <p>{{{ $manufacture_legend }}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <br>

                <h2 id="device-breakdown">Device breakdown</h2>

                <div class="row row-compressed-xs">
                    <div class="col-lg-5">
                        <ul class="properties properties__small">
                            <li>
                                <div>
                                @php( $group_device_count = 0 )

                                @if (isset($group_device_count_status[0]))
                                  @php( $group_device_count = (int)$group_device_count_status[0]->counter )
                                @endif

                                @if (isset($group_device_count_status[1]))
                                  @php( $group_device_count += (int)$group_device_count_status[1]->counter )
                                @endif

                                @if (isset($group_device_count_status[2]))
                                  @php( $group_device_count += (int)$group_device_count_status[2]->counter )
                                @endif

                                <h3>Total devices worked on</h3>
                                {{{ $group_device_count }}}
                                <svg width="18" height="16" viewBox="0 0 15 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M13.528,13.426l-12.056,0c-0.812,0 -1.472,-0.66 -1.472,-1.472l0,-7.933c0,-0.812 0.66,-1.472 1.472,-1.472l4.686,0l-1.426,-2.035c-0.059,-0.086 -0.039,-0.203 0.047,-0.263l0.309,-0.217c0.086,-0.06 0.204,-0.039 0.263,0.047l1.729,2.468l0.925,0l1.728,-2.468c0.06,-0.086 0.178,-0.107 0.263,-0.047l0.31,0.217c0.085,0.06 0.106,0.177 0.046,0.263l-1.425,2.035l4.601,0c0.812,0 1.472,0.66 1.472,1.472l0,7.933c0,0.812 -0.66,1.472 -1.472,1.472Zm-4.012,-9.499l-7.043,0c-0.607,0 -1.099,0.492 -1.099,1.099l0,5.923c0,0.607 0.492,1.099 1.099,1.099l7.043,0c0.606,0 1.099,-0.492 1.099,-1.099l0,-5.923c0,-0.607 -0.493,-1.099 -1.099,-1.099Zm3.439,3.248c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Zm0,-2.819c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Z" style="fill:#0394a6;"/></svg>
                                </div>
                            </li>
                            <li>
                                <div>
                                <h3>Fixed devices</h3>
                                @if (isset($group_device_count_status[0]))
                                  {{{ (int)$group_device_count_status[0]->counter }}}
                                @else
                                  0
                                @endif
                                <svg width="18" height="15" viewBox="0 0 14 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M6.601,1.38c1.344,-1.98 4.006,-1.564 5.351,-0.41c1.345,1.154 1.869,3.862 0,5.77c-1.607,1.639 -3.362,3.461 -5.379,4.615c-2.017,-1.154 -3.897,-3.028 -5.379,-4.615c-1.822,-1.953 -1.344,-4.616 0,-5.77c1.345,-1.154 4.062,-1.57 5.407,0.41Z" style="fill:#0394a6;"/></svg>
                                </div>
                            </li>
                            <li>
                                <div>
                                <h3>Repairable devices</h3>
                                @if (isset($group_device_count_status[1]))
                                  {{{ (int)$group_device_count_status[1]->counter }}}
                                @else
                                  0
                                @endif
                                <svg width="18" height="18" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.33,7.915l1.213,1.212c0.609,0.61 0.609,1.599 0,2.208l-2.208,2.208c-0.609,0.609 -1.598,0.609 -2.208,0l-1.212,-1.213l4.415,-4.415Zm-9.018,-6.811c0.609,-0.609 1.598,-0.609 2.207,0l1.213,1.213l-4.415,4.415l-1.213,-1.213c-0.609,-0.609 -0.609,-1.598 0,-2.207l2.208,-2.208Z" style="fill:#0394a6;"/><path d="M11.406,1.027c-0.61,-0.609 -1.599,-0.609 -2.208,0l-8.171,8.171c-0.609,0.609 -0.609,1.598 0,2.208l2.208,2.207c0.609,0.61 1.598,0.61 2.208,0l8.17,-8.17c0.61,-0.61 0.61,-1.599 0,-2.208l-2.207,-2.208Zm-4.373,8.359c0.162,-0.163 0.425,-0.163 0.588,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.162 -0.426,0.162 -0.588,0c-0.163,-0.162 -0.163,-0.426 0,-0.588Zm1.176,-1.177c0.163,-0.162 0.426,-0.162 0.589,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.163 -0.426,0.163 -0.589,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm-2.359,-0.006c0.162,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.426,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm3.536,-1.17c0.162,-0.163 0.426,-0.163 0.588,0c0.162,0.162 0.162,0.425 0,0.588c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.588Zm-2.359,-0.007c0.162,-0.162 0.426,-0.162 0.588,0c0.162,0.163 0.162,0.426 0,0.589c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.589Zm-2.361,-0.006c0.163,-0.163 0.426,-0.163 0.589,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.162 -0.426,0.162 -0.589,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm3.537,-1.17c0.162,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.426,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm-2.36,-0.007c0.163,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.425,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm1.177,-1.177c0.162,-0.162 0.426,-0.162 0.588,0c0.162,0.163 0.162,0.426 0,0.589c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.589Z" style="fill:#0394a6;"/></g></svg>
                                </div>
                            </li>
                            <li>
                                <div>
                                <h3>End-of-life devices</h3>
                                @if (isset($group_device_count_status[2]))
                                  {{{ (int)$group_device_count_status[2]->counter }}}
                                @else
                                  0
                                @endif
                                <svg width="20" height="20" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M2.382,10.651c-0.16,0.287 -0.287,0.719 -0.287,0.991c0,0.064 0,0.144 0.016,0.256l-1.999,-3.438c-0.064,-0.112 -0.112,-0.272 -0.112,-0.416c0,-0.145 0.048,-0.32 0.112,-0.432l0.959,-1.679l-1.071,-0.607l3.486,-0.065l1.695,3.054l-1.087,-0.623l-1.712,2.959Zm1.536,-9.691c0.303,-0.528 0.8,-0.816 1.407,-0.816c0.656,0 1.168,0.305 1.535,0.927l0.544,0.912l-1.887,3.263l-3.054,-1.775l1.455,-2.511Zm0.223,12.457c-0.911,0 -1.663,-0.752 -1.663,-1.663c0,-0.256 0.112,-0.688 0.272,-0.96l0.512,-0.911l3.79,0l0,3.534l-2.911,0l0,0Zm3.039,-12.553c-0.24,-0.415 -0.559,-0.704 -0.943,-0.864l3.933,0c0.352,0 0.624,0.144 0.784,0.417l0.976,1.662l1.055,-0.624l-1.696,3.039l-3.469,-0.049l1.071,-0.607l-1.711,-2.974Zm6.061,9.051c0.479,0 0.88,-0.128 1.215,-0.383l-1.983,3.453c-0.16,0.272 -0.447,0.432 -0.783,0.432l-1.872,0l0,1.231l-1.791,-2.99l1.791,-2.991l0,1.248l3.423,0l0,0Zm1.534,-2.879c0.145,0.256 0.225,0.528 0.225,0.816c0,0.576 -0.368,1.183 -0.879,1.471c-0.241,0.128 -0.577,0.209 -0.912,0.209l-1.056,0l-1.886,-3.263l3.054,-1.743l1.454,2.51Z" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg>
                                </div>
                            </li>
                            <li class="properties__item__full">
                                <div>
                                <h3>Most repaired devices</h3>
                                <div class="row row-compressed properties__repair-count">

                                    @for ($i=0; $i < 3; $i++)
                                      @if (isset($top[$i]))
                                        <div class="col-6"><strong>{{{ $top[$i]->name }}}: </strong></div>
                                        <div class="col-6">{{{ $top[$i]->counter }}}</div>
                                      @else
                                        <div class="col-12"><strong>{{{ $i+1 }}}.</strong> N/A</div>
                                      @endif
                                    @endfor

                                </div>
                                <svg width="18" height="16" viewBox="0 0 15 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M13.528,13.426l-12.056,0c-0.812,0 -1.472,-0.66 -1.472,-1.472l0,-7.933c0,-0.812 0.66,-1.472 1.472,-1.472l4.686,0l-1.426,-2.035c-0.059,-0.086 -0.039,-0.203 0.047,-0.263l0.309,-0.217c0.086,-0.06 0.204,-0.039 0.263,0.047l1.729,2.468l0.925,0l1.728,-2.468c0.06,-0.086 0.178,-0.107 0.263,-0.047l0.31,0.217c0.085,0.06 0.106,0.177 0.046,0.263l-1.425,2.035l4.601,0c0.812,0 1.472,0.66 1.472,1.472l0,7.933c0,0.812 -0.66,1.472 -1.472,1.472Zm-4.012,-9.499l-7.043,0c-0.607,0 -1.099,0.492 -1.099,1.099l0,5.923c0,0.607 0.492,1.099 1.099,1.099l7.043,0c0.606,0 1.099,-0.492 1.099,-1.099l0,-5.923c0,-0.607 -0.493,-1.099 -1.099,-1.099Zm3.439,3.248c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Zm0,-2.819c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Z" style="fill:#0394a6;"/></svg>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="col-lg-7">
                        @include('partials.group-device-breakdown')
                    </div>
                </div>

            </div>
        </div>
  </div>
</section>

@include('includes/modals/group-invite-to')
@include('includes/modals/group-description')
@include('includes/modals/group-volunteers')
@include('includes/modals/group-share-stats')

@endsection
