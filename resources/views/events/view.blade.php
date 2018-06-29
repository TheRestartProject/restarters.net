@extends('layouts.app')
@section('content')
<section class="events">
  <div class="container-fluid">

      <div class="events__header row align-content-top">
          <div class="col-lg-7 d-flex flex-column">

            <header>
                <h1>{{ $formdata->venue }}</h1>
                <p>Hosted by <a href="">{{ $formdata->group_name }}</a>, {{ $formdata->location }}</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{{ route('dashboard') }}}">FIXOMETER</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/') }}/groups">@lang('groups.groups')</a></li>
                        <li class="breadcrumb-item active" aria-current="page">The Mighty Restarters</li>
                    </ol>
                </nav>
                <img src="{{ asset('/images/placeholder.png') }}" alt="Placeholder" class="event-icon">
            </header>

          </div>
          <div class="col-lg-5">

            <div class="button-group button-group__r">
                <a href="{{ url('/') }}/party/edit/{{ $formdata->id }}" class="btn btn-primary">Edit event</a>
                <button data-toggle="modal" data-target="#event-share-stats" class="btn btn-primary">Event stats embed</a>
            </div>

          </div>
      </div>

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

        <div class="row">
            <div class="col-lg-4">

                <aside class="sidebar-lg-offset">

                    <h2>Event details</h2>
                    <div class="card events-card">
                        <div id="event-map" class="map" data-latitude="{{ $formdata->latitude }}" data-longitude="{{ $formdata->longitude }}" data-zoom="14"></div>

                        <div class="events-card__details">

                            <div class="row flex-row d-flex">

                                <div class="col-4 d-flex flex-column"><strong>Date: </strong></div>
                                <div class="col-8 d-flex flex-column">{{ date('D jS M Y', $formdata->event_date) }}</div>

                                <div class="col-4 d-flex flex-column"><strong>Time: </strong></div>
                                <div class="col-8 d-flex flex-column">{{ date('H:ia', $formdata->event_timestamp) }} - {{ date('H:ia', $formdata->event_end_timestamp) }}</div>

                                <div class="col-4 d-flex flex-column"><strong>Address: </strong></div>
                                <div class="col-8 d-flex flex-column"><address>{{ $formdata->location }}</address></div>

                                <div class="col-4 d-flex flex-column"><strong>Host: </strong></div>
                                @if(!empty($host))
                                  <div class="col-8 d-flex flex-column">{{ $host->name }}</div>
                                @else
                                  <div class="col-8 d-flex flex-column">None</div>
                                @endif

                                <div class="col-4 col-label d-flex flex-column"><strong>Participants:</strong></div>
                                <div class="col-8 d-flex flex-column">

                                    <div>

                                    <div class="input-group-qty">
                                        <label for="participants_qty" class="sr-only">Quantity:</label>
                                        <button class="increase btn-value">+</button>
                                        <input name="participants_qty" id="participants_qty" maxlength="3" value="{{ $formdata->pax }}" title="Qty" class="input-text form-control qty" type="number">
                                        <button class="decrease btn-value">–</button>
                                    </div>

                                    </div>

                                </div>
                            </div>

                        </div>

                    </div>
                    <h2>Event photos</h2>
                    <ul class="photo-list">
                      @foreach($images as $image)
                        <li>
                            <a href="/uploads/{{ $image->path }}" data-toggle="lightbox">
                              <img src="/uploads/{{ $image->path }}" alt="placeholder">
                            </a>
                        </li>
                      @endforeach
                    </ul>

                </aside>
            </div>
            <div class="col-lg-8">
                <h2 id="environmental-impact">Environmental impact</h2>
                <ul class="properties">
                    <li>
                        <div>
                        <h3>Waste prevented</h3>
                        {{ number_format(round($wasteTotal), 0, '.', ',') }} kg
                        <svg width="16" height="18" viewBox="0 0 13 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.15,0c0,0 -15.921,1.349 -11.313,10.348c0,0 0.59,-1.746 2.003,-3.457c0.852,-1.031 2,-2.143 3.463,-2.674c0.412,-0.149 0.696,0.435 0.094,0.727c0,0 -4.188,2.379 -4.732,6.112c0,0 1.805,1.462 3.519,1.384c1.714,-0.078 4.268,-1.078 4.707,-3.551c0.44,-2.472 1.245,-6.619 2.259,-8.889Z" style="fill:#0394a6;"/><path d="M1.147,13.369c0,0 0.157,-0.579 0.55,-2.427c0.394,-1.849 0.652,-0.132 0.652,-0.132l-0.25,2.576l-0.952,-0.017Z" style="fill:#0394a6;"/></g></svg>
                        </div>
                    </li>
                    <li>
                        <div>
                        <h3>CO<sub>2</sub> emissions prevented</h3>
                        {{ number_format(round($co2Total), 0, '.', ',') }} kg
                        <svg width="20" height="12" viewBox="0 0 15 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><circle cx="2.854" cy="6.346" r="2.854" style="fill:#0394a6;"/><circle cx="11.721" cy="5.92" r="3.279" style="fill:#0394a6;"/><circle cx="7.121" cy="4.6" r="4.6" style="fill:#0394a6;"/><rect x="2.854" y="6.346" width="8.867" height="2.854" style="fill:#0394a6;"/></g></svg>
                        </div>
                    </li>
                    <li>
                        <div>
                        <h3>Fixed devices</h3>
                        {{ $device_count_status[0]->counter }}
                        <svg width="17" height="15" viewBox="0 0 14 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M6.601,1.38c1.344,-1.98 4.006,-1.564 5.351,-0.41c1.345,1.154 1.869,3.862 0,5.77c-1.607,1.639 -3.362,3.461 -5.379,4.615c-2.017,-1.154 -3.897,-3.028 -5.379,-4.615c-1.822,-1.953 -1.344,-4.616 0,-5.77c1.345,-1.154 4.062,-1.57 5.407,0.41Z" style="fill:#0394a6;"/></svg>
                        </div>
                    </li>
                    <li>
                        <div>
                        <h3>Repairable devices</h3>
                        396
                        <svg width="20" height="20" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.33,7.915l1.213,1.212c0.609,0.61 0.609,1.599 0,2.208l-2.208,2.208c-0.609,0.609 -1.598,0.609 -2.208,0l-1.212,-1.213l4.415,-4.415Zm-9.018,-6.811c0.609,-0.609 1.598,-0.609 2.207,0l1.213,1.213l-4.415,4.415l-1.213,-1.213c-0.609,-0.609 -0.609,-1.598 0,-2.207l2.208,-2.208Z" style="fill:#0394a6;"/><path d="M11.406,1.027c-0.61,-0.609 -1.599,-0.609 -2.208,0l-8.171,8.171c-0.609,0.609 -0.609,1.598 0,2.208l2.208,2.207c0.609,0.61 1.598,0.61 2.208,0l8.17,-8.17c0.61,-0.61 0.61,-1.599 0,-2.208l-2.207,-2.208Zm-4.373,8.359c0.162,-0.163 0.425,-0.163 0.588,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.162 -0.426,0.162 -0.588,0c-0.163,-0.162 -0.163,-0.426 0,-0.588Zm1.176,-1.177c0.163,-0.162 0.426,-0.162 0.589,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.163 -0.426,0.163 -0.589,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm-2.359,-0.006c0.162,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.426,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm3.536,-1.17c0.162,-0.163 0.426,-0.163 0.588,0c0.162,0.162 0.162,0.425 0,0.588c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.588Zm-2.359,-0.007c0.162,-0.162 0.426,-0.162 0.588,0c0.162,0.163 0.162,0.426 0,0.589c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.589Zm-2.361,-0.006c0.163,-0.163 0.426,-0.163 0.589,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.162 -0.426,0.162 -0.589,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm3.537,-1.17c0.162,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.426,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm-2.36,-0.007c0.163,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.425,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm1.177,-1.177c0.162,-0.162 0.426,-0.162 0.588,0c0.162,0.163 0.162,0.426 0,0.589c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.589Z" style="fill:#0394a6;"/></g></svg>
                        </div>
                    </li>
                    <li>
                        <div>
                        <h3>Devices to be recycled</h3>
                        335
                        <svg width="20" height="20" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M2.382,10.651c-0.16,0.287 -0.287,0.719 -0.287,0.991c0,0.064 0,0.144 0.016,0.256l-1.999,-3.438c-0.064,-0.112 -0.112,-0.272 -0.112,-0.416c0,-0.145 0.048,-0.32 0.112,-0.432l0.959,-1.679l-1.071,-0.607l3.486,-0.065l1.695,3.054l-1.087,-0.623l-1.712,2.959Zm1.536,-9.691c0.303,-0.528 0.8,-0.816 1.407,-0.816c0.656,0 1.168,0.305 1.535,0.927l0.544,0.912l-1.887,3.263l-3.054,-1.775l1.455,-2.511Zm0.223,12.457c-0.911,0 -1.663,-0.752 -1.663,-1.663c0,-0.256 0.112,-0.688 0.272,-0.96l0.512,-0.911l3.79,0l0,3.534l-2.911,0l0,0Zm3.039,-12.553c-0.24,-0.415 -0.559,-0.704 -0.943,-0.864l3.933,0c0.352,0 0.624,0.144 0.784,0.417l0.976,1.662l1.055,-0.624l-1.696,3.039l-3.469,-0.049l1.071,-0.607l-1.711,-2.974Zm6.061,9.051c0.479,0 0.88,-0.128 1.215,-0.383l-1.983,3.453c-0.16,0.272 -0.447,0.432 -0.783,0.432l-1.872,0l0,1.231l-1.791,-2.99l1.791,-2.991l0,1.248l3.423,0l0,0Zm1.534,-2.879c0.145,0.256 0.225,0.528 0.225,0.816c0,0.576 -0.368,1.183 -0.879,1.471c-0.241,0.128 -0.577,0.209 -0.912,0.209l-1.056,0l-1.886,-3.263l3.054,-1.743l1.454,2.51Z" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg>
                        </div>
                    </li>
                </ul>
                <h2 id="description">Description</h2>
                <div class="events__description">
                    {{ str_limit(strip_tags($formdata->free_text), 440, '...') }}
                    @if( strlen($formdata->free_text) > 440 )
                      <button data-toggle="modal" data-target="#event-description"><span>Read more</span></button>
                    @endif
                </div>
                <h2 id="attendance">Attendance</h2>
                <ul class="nav nav-tabs" id="events-attendance" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="attended-tab" data-toggle="tab" href="#attended" role="tab" aria-controls="attended" aria-selected="true"><svg width="16" height="18" viewBox="0 0 12 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="top: 3px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M9.268,3.161c-0.332,-0.212 -0.776,-0.119 -0.992,0.207c-0.216,0.326 -0.122,0.763 0.21,0.975c1.303,0.834 2.08,2.241 2.08,3.766c0,1.523 -0.777,2.93 -2.078,3.764c-0.001,0.001 -0.001,0.001 -0.002,0.001c-0.741,0.475 -1.601,0.725 -2.486,0.725c-0.885,0 -1.745,-0.25 -2.486,-0.725c-0.001,0 -0.001,0 -0.001,0c-1.302,-0.834 -2.08,-2.241 -2.08,-3.765c0,-1.525 0.778,-2.932 2.081,-3.766c0.332,-0.212 0.426,-0.649 0.21,-0.975c-0.216,-0.326 -0.66,-0.419 -0.992,-0.207c-1.711,1.095 -2.732,2.945 -2.732,4.948c0,2.003 1.021,3.852 2.732,4.947c0,0 0.001,0.001 0.002,0.001c0.973,0.623 2.103,0.952 3.266,0.952c1.164,0 2.294,-0.33 3.268,-0.953c1.711,-1.095 2.732,-2.944 2.732,-4.947c0,-2.003 -1.021,-3.853 -2.732,-4.948" style="fill:#0394a6;fill-rule:nonzero;"/><path d="M7.59,2.133c0.107,-0.36 -0.047,-1.227 -0.503,-1.758c-0.214,0.301 -0.335,0.688 -0.44,1.022c-0.182,0.066 -0.364,-0.014 -0.581,-0.082c-0.116,-0.037 -0.505,-0.121 -0.584,-0.245c-0.074,-0.116 0.073,-0.249 0.146,-0.388c0.051,-0.094 0.094,-0.231 0.136,-0.337c0.049,-0.126 0.07,-0.247 -0.006,-0.345c-0.462,0.034 -1.144,0.404 -1.394,0.906c-0.067,0.133 -0.101,0.393 -0.089,0.519c0.011,0.104 0.097,0.313 0.161,0.424c0.249,0.426 0.588,0.781 0.766,1.206c0.22,0.525 0.172,0.969 0.182,1.52c0.041,2.214 -0.006,2.923 -0.01,5.109c0,0.189 -0.014,0.415 0.031,0.507c0.26,0.527 1.029,0.579 1.29,-0.001c0.087,-0.191 0.028,-0.571 0.017,-0.843c-0.033,-0.868 -0.056,-1.708 -0.08,-2.526c-0.033,-1.142 -0.06,-0.901 -0.117,-1.97c-0.028,-0.529 -0.023,-1.117 0.275,-1.629c0.141,-0.24 0.657,-0.78 0.8,-1.089" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg> Attended <span class="badge badge-pill badge-primary">{{ count($attended) }}</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="invited-tab" data-toggle="tab" href="#invited" role="tab" aria-controls="invited" aria-selected="false"><svg width="16" height="12" viewBox="0 0 12 9" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="top:0px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><g><ellipse cx="10.796" cy="1.139" rx="1.204" ry="1.139" style="fill:#0394a6;"/><ellipse cx="5.961" cy="4.5" rx="1.204" ry="1.139" style="fill:#0394a6;"/><ellipse cx="1.204" cy="1.139" rx="1.204" ry="1.139" style="fill:#0394a6;"/><path d="M10.796,0l-9.592,0l-0.753,2.031l4.823,3.409l0.687,0.199l0.643,-0.173l4.89,-3.397l-0.698,-2.069Z" style="fill:#0394a6;"/></g><path d="M12,2.59c0,-0.008 0,5.271 0,5.271c0,0.628 -0.539,1.139 -1.204,1.139c-0.052,0 -0.104,-0.003 -0.155,-0.009l-0.02,0.009l-9.417,0c-0.665,0 -1.204,-0.511 -1.204,-1.139c0,0 0,-4.602 0,-5.096c0,-0.028 0,-0.175 0,-0.175c0,0.004 0.176,0.329 0.452,0.538l-0.001,0.003l4.823,3.408l0.012,0.003c0.193,0.124 0.425,0.197 0.675,0.197c0.233,0 0.45,-0.063 0.634,-0.171l0.009,-0.002l0.045,-0.032c0.016,-0.01 0.031,-0.021 0.047,-0.032l4.798,-3.334l0,-0.001c0.306,-0.206 0.506,-0.568 0.506,-0.577Z" style="fill:#0394a6;"/></g></svg> Invited <span class="badge badge-pill badge-primary">{{ count($invited) }}</span></a>
                    </li>
                </ul>
                <div class="tab-content" id="events-attendance-tabs">
                    <div class="tab-pane fade show active" id="attended" role="tabpanel" aria-labelledby="attended-tab">
                        <div class="users-list-wrap">
                            <ul class="users-list">
                              @foreach ($attended as $attendee)
                                <li>
                                    <h3>{{ $attendee->name }}</h3>
                                    @if ( $attended_roles[$attendee->id] == 3 )
                                      <p><span class="badge badge-pill badge-primary">Host</span></p>
                                    @else
                                      <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                    @endif
                                    <button class="users-list__remove js-remove">Remove volunteer</button>
                                    @if (is_null($attendee->path))
                                      <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="Profile Picture" class="users-list__icon">
                                    @else
                                      <img src="/uploads/{{ $attendee->path }}" alt="Profile Picture" class="users-list__icon">
                                    @endif
                                </li>
                              @endforeach
                              @if (FixometerHelper::hasRole($user, 'Host') || FixometerHelper::hasRole($user, 'Administrator'))
                                <li class="users-list__add">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#event-invite-to">Add volunteer</button>
                                </li>
                              @endif
                            </ul>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="invited" role="tabpanel" aria-labelledby="invited-tab">
                        <div class="users-list-wrap">
                            <ul class="users-list">
                              @foreach ($invited as $invitee)
                                <li>
                                    <h3>{{ $invitee->name }}</h3>
                                    @if ( $invited_roles[$invitee->id] == 3 )
                                      <p><span class="badge badge-pill badge-primary">Host</span></p>
                                    @else
                                      <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                    @endif
                                    <button class="users-list__remove js-remove">Remove volunteer</button>
                                    @if (is_null($invitee->path))
                                      <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="Profile Picture" class="users-list__icon">
                                    @else
                                      <img src="/uploads/{{ $invitee->path }}" alt="Profile Picture" class="users-list__icon">
                                    @endif
                                </li>
                              @endforeach
                              @if (FixometerHelper::hasRole($user, 'Host') || FixometerHelper::hasRole($user, 'Administrator'))
                                <li class="users-list__add">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#event-invite-to">Add volunteer</button>
                                </li>
                              @endif
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-lg-12">
                <h2><svg width="20" height="18" viewBox="0 0 15 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="position:relative;z-index:1;top:2px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M13.528,13.426l-12.056,0c-0.812,0 -1.472,-0.66 -1.472,-1.472l0,-7.933c0,-0.812 0.66,-1.472 1.472,-1.472l4.686,0l-1.426,-2.035c-0.059,-0.086 -0.039,-0.203 0.047,-0.263l0.309,-0.217c0.086,-0.06 0.204,-0.039 0.263,0.047l1.729,2.468l0.925,0l1.728,-2.468c0.06,-0.086 0.178,-0.107 0.263,-0.047l0.31,0.217c0.085,0.06 0.106,0.177 0.046,0.263l-1.425,2.035l4.601,0c0.812,0 1.472,0.66 1.472,1.472l0,7.933c0,0.812 -0.66,1.472 -1.472,1.472Zm-4.012,-9.499l-7.043,0c-0.607,0 -1.099,0.492 -1.099,1.099l0,5.923c0,0.607 0.492,1.099 1.099,1.099l7.043,0c0.606,0 1.099,-0.492 1.099,-1.099l0,-5.923c0,-0.607 -0.493,-1.099 -1.099,-1.099Zm3.439,3.248c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Zm0,-2.819c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Z" style="fill:#0394a6;"/></svg> Devices <span class="badge badge-pill badge-primary">{{ count($formdata->devices) }}</span></h2>
                <div class="table-responsive">
                    <table class="table table-repair" role="table">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="text-center"><svg width="22" height="17" viewBox="0 0 17 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="position:relative;z-index:1;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Camera</title><path d="M8.25,4.781c-1.367,0 -2.475,1.071 -2.475,2.391c0,1.32 1.108,2.39 2.475,2.39c1.367,0 2.475,-1.07 2.475,-2.39c0,-1.32 -1.108,-2.391 -2.475,-2.391Zm6.6,-2.39l-1.98,0c-0.272,0 -0.566,-0.204 -0.652,-0.454l-0.511,-1.484c-0.087,-0.249 -0.38,-0.453 -0.652,-0.453l-5.61,0c-0.272,0 -0.566,0.204 -0.652,0.454l-0.511,1.483c-0.087,0.25 -0.38,0.454 -0.652,0.454l-1.98,0c-0.908,0 -1.65,0.717 -1.65,1.593l0,7.172c0,0.877 0.742,1.594 1.65,1.594l13.2,0c0.907,0 1.65,-0.717 1.65,-1.594l0,-7.172c0,-0.876 -0.743,-1.593 -1.65,-1.593Zm-6.6,8.765c-2.278,0 -4.125,-1.784 -4.125,-3.984c0,-2.2 1.847,-3.985 4.125,-3.985c2.278,0 4.125,1.785 4.125,3.985c0,2.2 -1.847,3.984 -4.125,3.984Zm6.022,-6.057c-0.318,0 -0.577,-0.25 -0.577,-0.558c0,-0.308 0.259,-0.558 0.577,-0.558c0.32,0 0.578,0.25 0.578,0.558c0,0.308 -0.259,0.558 -0.578,0.558Z" style="fill:#0394a6;fill-rule:nonzero;"/></svg></th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Age</th>
                                <th>Description of problem/solution</th>
                                <th>Status</th>
                                <th><span class="sr-only">Repairable: more information</span></th>
                                <th class="text-center">Spare parts:</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php( $i = 1)
                            @foreach($formdata->devices as $device)
                              <tr>
                                  <td><a class="collapsed row-button" data-toggle="collapse" href="#row-{{ $device->iddevices }}" role="button" aria-expanded="false" aria-controls="row-1">Edit <span class="arrow">▴</span></a></td>
                                  <td class="text-center"><a href="#">{{ $device->iddevices }}</a></td>
                                  <td>{{ $device->name }}</td>
                                  <td>{{ $device->brand }}</td>
                                  <td>{{ $device->model }}</td>
                                  <td>{{ $device->age }}</td>
                                  <td>{!! $device->problem !!}</td>
                                  @if ( $device->repair_status == 1 )
                                    <td><span class="badge badge-success">Fixed</span></td>
                                  @elseif ( $device->repair_status == 2 )
                                    <td><span class="badge badge-warning">Repairable</span></td>
                                  @else
                                    <td><span class="badge badge-danger">End</span></td>
                                  @endif
                                  @if ($device->more_time_needed == 1)
                                    <td>More time needed</td>
                                  @elseif ($device->professional_help == 1)
                                    <td>Professional help</td>
                                  @elseif ($device->do_it_yourself == 1)
                                    <td>Do it yourself</td>
                                  @else
                                    <td>N/A</td>
                                  @endif
                                  @if ($device->spare_parts == 1)
                                    <td class="text-center"><svg class="table-tick" width="21" height="17" viewBox="0 0 16 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;position:relative;z-index:1"><g><path d="M5.866,12.648l2.932,-2.933l-5.865,-5.866l-2.933,2.933l5.866,5.866Z" style="fill:#0394a6;"/><path d="M15.581,2.933l-2.933,-2.933l-9.715,9.715l2.933,2.933l9.715,-9.715Z" style="fill:#0394a6;"/></g></svg></td>
                                  @else
                                    <td class="text-center"><svg width="15" height="15" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Close</title><g><g opacity="0.5"><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></g></svg></td>
                                  @endif
                                  <td><a class="collapsed row-button" data-toggle="collapse"  href="#row-{{ $device->iddevices }}" role="button" aria-expanded="false" aria-controls="row-1"><svg width="15" height="15" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Close</title><g><g opacity="0.5"><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></g></svg></a></td>
                              </tr>
                              <tr class="collapse table-row-details" id="row-{{ $device->iddevices }}">
                                  <td colspan="11">
                                      <form id="data-{{ $device->iddevices }}" class="edit-device" data-device="{{ $device->iddevices }}" method="post" enctype="multipart/form-data">
                                      <table class="table">
                                          <tbody>
                                              <tr>
                                                  <td>
                                                      <label for="status-{{ $device->iddevices }}">Status:</label>
                                                      <div class="form-control form-control__select">
                                                          <select class="checkStatus" name="status" id="status-{{ $device->iddevices }}" data-device="{{ $device->iddevices }}">
                                                            @if ( $device->repair_status == 1 )
                                                              <option value="1" selected>Fixed</option>
                                                              <option value="2">Repairable</option>
                                                              <option value="3">End of Life</option>
                                                            @elseif ( $device->repair_status == 2 )
                                                              <option value="1">Fixed</option>
                                                              <option value="2" selected>Repairable</option>
                                                              <option value="3">End of Life</option>
                                                            @else
                                                              <option value="1">Fixed</option>
                                                              <option value="2">Repairable</option>
                                                              <option value="3" selected>End of Life</option>
                                                            @endif
                                                          </select>
                                                      </div>
                                                  </td>
                                                  <td>
                                                      <label for="repair-info-{{ $device->iddevices }}" class="sr-only">Repairable: more information</label>
                                                      <div class="form-control form-control__select">
                                                          <select name="repair-info" id="repair-info-{{ $device->iddevices }}" disabled>
                                                            <option value="0">Repair Details</option>
                                                            @if ( $device->more_time_needed == 1 )
                                                              <option value="1" selected>More time needed</option>
                                                              <option value="2">Professional help</option>
                                                              <option value="3">Do it yourself</option>
                                                            @elseif ( $device->professional_help == 1 )
                                                              <option value="1">More time needed</option>
                                                              <option value="2" selected>Professional help</option>
                                                              <option value="3">Do it yourself</option>
                                                            @elseif ( $device->do_it_yourself == 1 )
                                                              <option value="1" >More time needed</option>
                                                              <option value="2">Professional help</option>
                                                              <option value="3" selected>Do it yourself</option>
                                                            @else
                                                              <option value="1">More time needed</option>
                                                              <option value="2">Professional help</option>
                                                              <option value="3">Do it yourself</option>
                                                            @endif
                                                          </select>
                                                      </div>
                                                  </td>
                                                  <td>
                                                      <label for="spare_parts">Spare parts:</label>
                                                      <div class="form-control form-control__select">
                                                          <select name="spare-parts-{{ $device->iddevices }}" id="spare-parts-{{ $device->iddevices }}">
                                                            @if ($device->spare_parts == 1)
                                                              <option value="1" selected>Yes</option>
                                                              <option value="2">No</option>
                                                            @else
                                                              <option value="1">Yes</option>
                                                              <option value="2" selected>No</option>
                                                            @endif
                                                          </select>
                                                      </div>
                                                  </td>
                                                  <td>
                                                      <label for="category">Category:</label>
                                                      <div class="form-control form-control__select">
                                                          <select name="category-{{ $device->iddevices }}" id="category-{{ $device->iddevices }}">
                                                              @foreach($categories as $category)
                                                                @if ($device->category == $category->idcategories)
                                                                  <option value="{{ $category->idcategories }}" selected>{{ $category->name }}</option>
                                                                @else
                                                                  <option value="{{ $category->idcategories }}">{{ $category->name }}</option>
                                                                @endif
                                                              @endforeach
                                                          </select>
                                                      </div>
                                                  </td>
                                                  <td>
                                                      <label for="nested-5">Brand:</label>
                                                      <div class="form-control form-control__select">
                                                          <select name="brand-{{ $device->iddevices }}" id="brand-{{ $device->iddevices }}">
                                                              @foreach($brands as $brand)
                                                                @if ($device->brand == $brand->brand_name)
                                                                  <option value="{{ $brand->id }}" selected>{{ $brand->brand_name }}</option>
                                                                @else
                                                                  <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                                                                @endif
                                                              @endforeach
                                                          </select>
                                                      </div>
                                                  </td>
                                                  <td>
                                                      <label for="nested-6">Model:</label>
                                                      <div class="form-group">
                                                          <input type="text" class="form-control field" id="model-{{ $device->iddevices }}" name="model-{{ $device->iddevices }}" value="{{ $device->model }}">
                                                      </div>
                                                  </td>
                                                  <td>
                                                      <label for="nested-7">Age:</label>
                                                      <div class="form-group">
                                                          <input type="text" class="form-control field" id="age-{{ $device->iddevices }}" name="age-{{ $device->iddevices }}" value="{{ $device->age }}">
                                                      </div>
                                                  </td>
                                              </tr>
                                              <tr class="table-row-more">
                                                  <td colspan="5">
                                                      <label for="description">Description of problem/solution:</label>
                                                      <div class="rte" id="problem-{{ $device->iddevices }}">{!! $device->problem !!}</div>
                                                  </td>
                                                  <td colspan="2" class="table-cell-upload-td">
                                                      <div class="table-cell-upload">
                                                          <div class="form-group">
                                                              <label for="file">Add image:</label>

                                                              <form id="dropzoneEl" class="dropzone" action="/device/image-upload/{{ $device->iddevices }}" method="post" enctype="multipart/form-data" data-field1="Add device images here" data-field2="Choose compelling images that show off your work">
                                                                  <div class="fallback" >
                                                                      <input id="file-{{ $device->iddevices }}" name="file-{{ $device->iddevices }}" type="file" multiple />
                                                                  </div>
                                                              </form>

                                                              <div class="previews"></div>

                                                          </div>

                                                          <div class="row">
                                                              <div class="col-9 d-flex align-content-center flex-column">
                                                                  <div class="form-check d-flex align-items-center justify-content-start">
                                                                      <input class="form-check-input" type="checkbox" name="wiki-{{ $device->iddevices }}" id="wiki-{{ $device->iddevices }}" value="1">
                                                                      <label class="form-check-label" for="opt">Solution is suitable for the Restart Wiki</label>
                                                                  </div>
                                                              </div>
                                                              <div class="col-3 d-flex justify-content-end flex-column"><div class="d-flex justify-content-end">
                                                                  <button type="submit" class="btn btn-primary btn-save2">Save</button></div>
                                                              </div>
                                                          </div>
                                                      </div>
                                                  </td>
                                              </tr>
                                          </tbody>
                                      </table>
                                      </form>
                                  </td>
                              </tr>
                              <?php
                                // $i++;
                                if($i == 6){
                                  break;
                                }
                              ?>
                            @endforeach
                            <!--
                              <tr>
                                  <td><a class="collapsed row-button" data-toggle="collapse" href="#row-1" role="button" aria-expanded="false" aria-controls="row-1">Edit <span class="arrow">▴</span></a></td>
                                  <td class="text-center"><a href="#">12</a></td>
                                  <td>Flat screen 26-30"</td>
                                  <td>Toshiba</td>
                                  <td>RC1900</td>
                                  <td>3 years</td>
                                  <td>Hair straightener - No power</td>
                                  <td><span class="badge badge-success">Fixed</span></td>
                                  <td>More time</td>
                                  <td class="text-center"><svg class="table-tick" width="21" height="17" viewBox="0 0 16 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;position:relative;z-index:1"><g><path d="M5.866,12.648l2.932,-2.933l-5.865,-5.866l-2.933,2.933l5.866,5.866Z" style="fill:#0394a6;"/><path d="M15.581,2.933l-2.933,-2.933l-9.715,9.715l2.933,2.933l9.715,-9.715Z" style="fill:#0394a6;"/></g></svg></td>
                                  <td><a class="collapsed row-button" data-toggle="collapse"  href="#row-1" role="button" aria-expanded="false" aria-controls="row-1"><svg width="15" height="15" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Close</title><g><g opacity="0.5"><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></g></svg></a></td>
                              </tr>
                              <tr class="collapse table-row-details" id="row-1">
                                <td colspan="11">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <label for="nested-1">Status:</label>
                                                    <div class="form-control form-control__select">
                                                        <select name="nested-1" id="nested-1">
                                                            <option value="">Options</option>
                                                            <option value="">Options</option>
                                                            <option value="">Options</option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td>
                                                    <label for="nested-2" class="sr-only">Repairable: more information</label>
                                                    <div class="form-control form-control__select">
                                                        <select name="nested-2" id="nested-2" disabled>
                                                            <option value="">Yes</option>
                                                            <option value="">No</option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td>
                                                    <label for="nested-3">Spare parts:</label>
                                                    <div class="form-control form-control__select">
                                                        <select name="nested-3" id="nested-3">
                                                            <option value="">Yes</option>
                                                            <option value="">No</option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td>
                                                    <label for="nested-4">Category:</label>
                                                    <div class="form-control form-control__select">
                                                        <select name="nested-4" id="nested-4">
                                                            <option value="">Options</option>
                                                            <option value="">Options</option>
                                                            <option value="">Options</option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td>
                                                    <label for="nested-5">Brand:</label>
                                                    <div class="form-control form-control__select">
                                                        <select name="nested-5" id="nested-5">
                                                            <option value="">Options</option>
                                                            <option value="">Options</option>
                                                            <option value="">Options</option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td>
                                                    <label for="nested-6">Model:</label>
                                                    <div class="form-group">
                                                        <input type="text" class="form-control field" id="nested-6" name="nested-6">
                                                    </div>
                                                </td>
                                                <td>
                                                    <label for="nested-7">Age:</label>
                                                    <div class="form-group">
                                                        <input type="text" class="form-control field" id="nested-7" name="nested-7">
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="table-row-more">
                                                <td colspan="5">
                                                    <label for="description">Description of problem/solution:</label>
                                                    <div class="rte"></div>
                                                </td>
                                                <td colspan="2" class="table-cell-upload-td">
                                                    <div class="table-cell-upload">
                                                        <div class="form-group">
                                                            <label for="file">Add image:</label>

                                                            <form id="dropzoneEl" class="dropzone" action="/" method="post" enctype="multipart/form-data" data-field1="Add device images here" data-field2="Choose compelling images that show off your work">
                                                                <div class="fallback">
                                                                    <input id="file" name="file" type="file" multiple />
                                                                </div>
                                                            </form>

                                                            <div class="previews"></div>

                                                        </div>

                                                        <div class="row">
                                                            <div class="col-9 d-flex align-content-center flex-column">
                                                                <div class="form-check d-flex align-items-center justify-content-start">
                                                                    <input class="form-check-input" type="checkbox" name="opt-checkboxes" id="opt" value="option1">
                                                                    <label class="form-check-label" for="opt">Solution is suitable for the Restart Wiki</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-3 d-flex justify-content-end flex-column"><div class="d-flex justify-content-end">
                                                                <button type="submit" class="btn btn-primary btn-save2">Save</button></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr> -->
                            <!-- <tr>
                                <td><a class="collapsed row-button" data-toggle="collapse" href="#row-2" role="button" aria-expanded="false" aria-controls="row-2">Edit <span class="arrow">▴</span></a></td>
                                <td class="text-center"><a href="#">12</a></td>
                                <td>Flat screen 26-30"</td>
                                <td>Toshiba</td>
                                <td>RC1900</td>
                                <td>3 years</td>
                                <td>Hair straightener - No power</td>
                                <td><span class="badge badge-danger">End</span></td>
                                <td>More time</td>
                                <td class="text-center"><svg class="table-tick" width="21" height="17" viewBox="0 0 16 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;position:relative;z-index:1"><g><path d="M5.866,12.648l2.932,-2.933l-5.865,-5.866l-2.933,2.933l5.866,5.866Z" style="fill:#0394a6;"/><path d="M15.581,2.933l-2.933,-2.933l-9.715,9.715l2.933,2.933l9.715,-9.715Z" style="fill:#0394a6;"/></g></svg></td>
                                <td><a class="collapsed row-button" data-toggle="collapse" href="#row-2" role="button" aria-expanded="false" aria-controls="row-2"><svg width="15" height="15" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Close</title><g><g opacity="0.5"><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></g></svg></a></td>
                            </tr>
                            <tr class="collapse" id="row-2">
                                <td colspan="11">
                                    row 2
                                </td>
                            </tr> -->
                            <!-- <tr>
                                <td><a class="collapsed row-button" data-toggle="collapse" href="#row-3" role="button" aria-expanded="false" aria-controls="row-3">Edit <span class="arrow">▴</span></a></td>
                                <td class="text-center"><a href="#">12</a></td>
                                <td>Flat screen 26-30"</td>
                                <td>Toshiba</td>
                                <td>RC1900</td>
                                <td>3 years</td>
                                <td>Hair straightener - No power</td>
                                <td><span class="badge badge-warning">Repairable</span></td>
                                <td>More time</td>
                                <td class="text-center"><svg class="table-tick" width="21" height="17" viewBox="0 0 16 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;position:relative;z-index:1"><g><path d="M5.866,12.648l2.932,-2.933l-5.865,-5.866l-2.933,2.933l5.866,5.866Z" style="fill:#0394a6;"/><path d="M15.581,2.933l-2.933,-2.933l-9.715,9.715l2.933,2.933l9.715,-9.715Z" style="fill:#0394a6;"/></g></svg></td>
                                <td><a class="collapsed row-button" data-toggle="collapse" href="#row-3" role="button" aria-expanded="false" aria-controls="row-3"><svg width="15" height="15" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Close</title><g><g opacity="0.5"><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></g></svg></a></td>
                            </tr>
                            <tr class="collapse" id="row-3">
                                <td colspan="11">
                                    row 3
                                </td>
                            </tr> -->
                        </tbody>
                    </table>
                </div>
                <div class="table-responsive">
                    <form id="add-device">
                    <table class="table table-add" role="table">
                        <tbody>
                            <tr>
                                <th width="100">Add device</th>
                                  <td>
                                    <div class="form-control form-control__select">
                                        <select name="repair_status" id="repair_status">
                                            <option value="0">Status</option>
                                            <option value="1">Fixed</option>
                                            <option value="2">Repairable</option>
                                            <option value="3">End of Life</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-control form-control__select">
                                        <select name="repair_details" id="repair_details" disabled>
                                            <option value="0">Repair Details</option>
                                            <option value="1">More time needed</option>
                                            <option value="2">Professional help</option>
                                            <option value="3">Do it yourself</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                <td>
                                    <div class="form-control form-control__select">
                                        <select name="spare_parts" id="spare_parts">
                                            <option value="0">Spare Parts Needed?</option>
                                            <option value="1">Yes</option>
                                            <option value="2">No</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-control form-control__select">
                                        <select name="category" id="category">
                                            <option value="0">--- Category ---</option>
                                            @foreach($categories as $category)
                                              <option value="{{ $category->idcategories }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-control form-control__select">
                                        <select name="brand" id="brand">
                                            <option value="0">--- Brand ---</option>
                                            @foreach($brands as $brand)
                                              <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input type="text" class="form-control field" id="model" name="model" placeholder="Model" required>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input type="text" class="form-control field" id="age" name="age" placeholder="Age" required>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input type="text" class="form-control field" id="problem" name="problem" placeholder="Problem" required>
                                    </div>
                                </td>
                                <td><input type="submit" class="btn btn-primary btn-add" id="submit-new-device" value="Add"></td>
                            </tr>
                        </tbody>
                    </table>
                    </form>

                </div>
            </div>
        </div>
  </div>
</section>

@include('includes.modals.event-invite-to')
@include('includes.modals.event-description')
@include('includes.modals.event-share-stats')
@include('includes.modals.event-all-volunteers')
@include('includes.modals.event-all-attended')
@include('includes.modals.event-add-volunteer')

@endsection
