@extends('layouts.app')
@section('content')
<section class="groups">
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="d-flex justify-content-between align-content-center">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{{ route('dashboard') }}}">FIXOMETER</a></li>
                <li class="breadcrumb-item"><a href="{{{ route('events') }}}">@lang('events.events')</a></li>
                <li class="breadcrumb-item"><a href="/party/view/{{ $formdata->id }}">{{ str_limit($formdata->venue, 20, '...') }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">@lang('events.edit_event')</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-12">
        @if(isset($response))
          @php( FixometerHelper::printResponse($response) )
        @endif

        <ul class="nav nav-tabs">
          <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#details">Event details</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#photos">Event photos</a>
          </li>
          @if( $audits && FixometerHelper::hasRole(Auth::user(), 'Administrator') )
          <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#log">Event log</a>
          </li>
          @endif
        </ul>

        <div class="edit-panel">

          <div class="tab-content">

            <div class="tab-pane active" id="details">

              <div class="row">
                <div class="col-lg-12">
                  <div class="form-group__offset">
                  <h4>@lang('events.edit_event')</h4>
                  <!-- <p>@lang('events.edit_event_content')</p>-->
                  </div>
                </div>
              </div>

              <form action="/party/edit/<?php echo $formdata->id; ?>" method="post" id="party-edit" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="<?php echo $formdata->id; ?>" >

                <div class="row">
                  <div class="col-lg-6">
                    <div class="form-group form-group__offset">
                      <label for="event_name">@lang('events.field_event_name'):</label>
                      <input type="text" class="form-control field" id="event_name" name="venue" value="{{ $formdata->venue }}" placeholder="@lang('events.field_event_name_helper')">
                    </div>

                  @if ( ( FixometerHelper::hasRole($user, 'Host') && count($user_groups) > 1 ) || FixometerHelper::hasRole($user, 'Administrator') )
                  <div class="form-group form-group__offset">
                      <label for="event_group">@lang('events.field_event_group'):</label>
                      <div class="form-control form-control__select">
                        <select name="group" id="event_group" class="field field select2" required>
                          <option></option>
                          @foreach($group_list as $group)
                            @if( FixometerHelper::hasRole($user, 'Administrator') || in_array($group->id, $user_groups) )
                              <option value="<?php echo $group->id; ?>" <?php echo($group->id == $formdata->group ? 'selected' : ''); ?>><?php echo $group->name; ?></option>
                            @endif
                          @endforeach
                        </select>
                      </div>
                  </div>
                  @else
                    <input type="hidden" name="group" value="{{ $user_groups[0] }}">
                  @endif

                  <div class="form-group">
                    <label for="event_desc">@lang('events.field_event_desc'):</label>
                    <div class="rte" name="description" id="description">{!! $formdata->free_text !!}</div>
                  </div>

                  <input type="hidden" name="free_text" id="free_text" value="{{ $formdata->free_text }}">
                </div>

                <div class="col-lg-6">
                  <div class="form-group">
                    <div class="row">
                      <div class="col-lg-7">
                        <label for="event_date">@lang('events.field_event_date'):</label>
                        <input type="date" id="event_date" name="event_date" class="form-control field" value="<?php echo date('Y-m-d', $formdata->event_date); ?>">
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="row">
                      <div class="col-lg-7">
                           <div class="form-group">
                        <label for="field_event_time">@lang('events.field_event_time'):</label>
                        <div class="row row-compressed">

                          <div class="col-6">
                            <input type="time" id="start-time" name="start" class="form-control field" value="{{ date('H:i', strtotime($formdata->start)) }}">
                          </div>
                          <div class="col-6">
                            <label class="sr-only" for="field_event_time_2">@lang('events.field_event_time'):</label>
                            <input type="time" id="end-time" name="end" class="form-control field" value="{{ date('H:i', strtotime($formdata->end)) }}">
                          </div>
                          </div>

                        </div>
                      </div>
                      <div class="col-12">

                          <div class="row row-compressed">
                              <div class="col-md-7">
                                <div class="form-group">
                                  <label for="autocomplete">@lang('events.field_event_venue'):</label>
                                  <input type="text" placeholder="Enter your address" id="autocomplete" name="location" class="form-control field field-geolocate" aria-describedby="locationHelpBlock" value="{{ $formdata->location }}"/>

                                  <small id="locationHelpBlock" class="form-text text-muted">
                                    @lang('events.field_venue_helper')
                                  </small>

                                  <input type="hidden" id="street_number" disabled="true">
                                  <input type="hidden" id="route" disabled="true">
                                  <input type="hidden" id="locality" disabled="true">
                                  <input type="hidden" id="administrative_area_level_1" disabled="true">
                                  <input type="hidden" id="postal_code" disabled="true">
                                  <input type="hidden" id="country" disabled="true">

                                </div>
                              </div>
                              <div class="col-lg-5">
                                <div id="map-plugin" class="map events__map" data-latitude="{{ $formdata->latitude }}" data-longitude="{{ $formdata->longitude }}" data-zoom="14"></div>
                              </div>
                          </div>

                      </div>
                    </div>

                    @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') && is_null($formdata->wordpress_post_id) )
                    <div class="form-group">
                      <div class="row">
                        <div class="col-lg-7">

                          <label class="groups-tags-label" for="moderate"><svg width="18" height="18" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M7.5 1.58a5.941 5.941 0 0 1 5.939 5.938A5.942 5.942 0 0 1 7.5 13.457a5.942 5.942 0 0 1-5.939-5.939A5.941 5.941 0 0 1 7.5 1.58zm0 3.04a2.899 2.899 0 1 1-2.898 2.899A2.9 2.9 0 0 1 7.5 4.62z"></path><ellipse cx="6.472" cy=".217" rx=".274" ry=".217"></ellipse><ellipse cx="8.528" cy=".217" rx=".274" ry=".217"></ellipse><path d="M6.472 0h2.056v1.394H6.472z"></path><path d="M8.802.217H6.198l-.274 1.562h3.152L8.802.217z"></path><ellipse cx="8.528" cy="14.783" rx=".274" ry=".217"></ellipse><ellipse cx="6.472" cy="14.783" rx=".274" ry=".217"></ellipse><path d="M6.472 13.606h2.056V15H6.472z"></path><path d="M6.198 14.783h2.604l.274-1.562H5.924l.274 1.562zM1.47 2.923c.107-.106.262-.125.347-.04.084.085.066.24-.041.347-.107.107-.262.125-.346.04-.085-.084-.067-.24.04-.347zM2.923 1.47c.107-.107.263-.125.347-.04.085.084.067.239-.04.346-.107.107-.262.125-.347.041-.085-.085-.066-.24.04-.347z"></path><path d="M2.923 1.47L1.47 2.923l.986.986 1.453-1.453-.986-.986z"></path><path d="M3.27 1.43L1.43 3.27l.91 1.299L4.569 2.34 3.27 1.43zm10.26 10.647c-.107.106-.262.125-.347.04-.084-.085-.066-.24.041-.347.107-.107.262-.125.346-.04.085.084.067.24-.04.347zm-1.453 1.453c-.107.107-.263.125-.347.04-.085-.084-.067-.239.04-.346.107-.107.262-.125.347-.041.085.085.066.24-.04.347z"></path><path d="M12.077 13.53l1.453-1.453-.986-.986-1.453 1.453.986.986z"></path><path d="M11.73 13.57l1.84-1.84-.91-1.299-2.229 2.229 1.299.91zM0 8.528c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274zm0-2.056c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274z"></path><path d="M0 6.472v2.056h1.394V6.472H0z"></path><path d="M.217 6.198v2.604l1.562.274V5.924l-1.562.274zM15 6.472c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274zm0 2.056c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274z"></path><path d="M15 8.528V6.472h-1.394v2.056H15z"></path><path d="M14.783 8.802V6.198l-1.562-.274v3.152l1.562-.274zM2.923 13.53c-.106-.107-.125-.262-.04-.347.085-.084.24-.066.347.041.107.107.125.262.04.346-.084.085-.24.067-.347-.04zM1.47 12.077c-.107-.107-.125-.263-.04-.347.084-.085.239-.067.346.04.107.107.125.262.041.347-.085.085-.24.066-.347-.04z"></path><path d="M1.47 12.077l1.453 1.453.986-.986-1.453-1.453-.986.986z"></path><path d="M1.43 11.73l1.84 1.84 1.299-.91-2.229-2.229-.91 1.299zM12.077 1.47c.106.107.125.262.04.347-.085.084-.24.066-.347-.041-.107-.107-.125-.262-.04-.346.084-.085.24-.067.347.04zm1.453 1.453c.107.107.125.263.04.347-.084.085-.239.067-.346-.04-.107-.107-.125-.262-.041-.347.085-.085.24-.066.347.04z"></path><path d="M13.53 2.923L12.077 1.47l-.986.986 1.453 1.453.986-.986z"></path><path d="M13.57 3.27l-1.84-1.84-1.299.91 2.229 2.229.91-1.299z"></path></g></svg> @lang('events.approve_event')</label>
                          <select name="moderate" class="form-control field">
                            <option></option>
                            <option value="approve">Approve</option>
                          </select>

                          <small id="locationHelpBlock" class="form-text text-muted">
                            This will mark the post as having been moderated and will send all hosts an email confirming
                          </small>

                        </div>
                      </div>

                    </div>
                    @endif

                  </div>

                </div>
              </div>

              <div class="button-group row">
                  <div class="offset-lg-3 col-lg-7 d-flex align-items-right justify-content-end text-right">
                      @if( is_null($formdata->wordpress_post_id) )
                        <span class="button-group__notice text-right">@lang('events.before_submit_text')</span>
                      @endif
                  </div>
                  <div class="col-lg-2 d-flex align-items-center justify-content-end">
                      <input type="submit" class="btn btn-primary btn-block btn-create" id="create-event" value="@lang('events.save_event')">
                  </div>
              </div>

            </form>

          </div>

          <div class="tab-pane" id="photos">
            <div class="form-group row">

                <div class="col-6 col-lg-6">
                  <label for="file">@lang('events.field_add_image'):</label>
                  <form id="dropzoneEl-{{ $formdata->id }}" data-deviceid="{{ $formdata->id }}" class="dropzone dropzoneEl" action="/party/image-upload/{{ $formdata->id }}" method="post" enctype="multipart/form-data" data-field1="@lang('events.field_event_images')" data-field2="@lang('events.field_event_images_2')">
                      @csrf
                      <div class="dz-default dz-message"></div>
                      <div class="fallback">
                          <input id="file-{{ $formdata->id }}" name="file-{{ $formdata->id }}" type="file" multiple />
                      </div>
                  </form>
                </div>

                <div class="col-6 col-lg-6">
                  <div class="previews">
                    @if( !empty($images) )
                      @foreach($images as $image)
                        <div id="device-image-{{ $formdata->id }}" class="dz-image">
                          <a href="/uploads/{{ $image->path }}" data-toggle="lightbox">
                          <img src="/uploads/thumbnail_{{ $image->path }}" alt="placeholder"></a>
                          <a href="/party/image/delete/{{ $formdata->id }}/{{{ $image->idimages }}}/{{{ $image->path }}}" data-device-id="{{ $formdata->id }}" class="dz-remove ajax-delete-image">Remove file</a>
                        </div>
                      @endforeach
                    @endif
                    <div class="uploads-{{ $formdata->id }}"></div>
                  </div>
                </div>

            </div>

          </div>

          @if( $audits && FixometerHelper::hasRole(Auth::user(), 'Administrator') )
            <div class="tab-pane" id="log">

              <div class="row">
                <div class="col">
                  <h4>Event changes</h4>
                  <p>Changes made on event <strong>{{ $formdata->venue }}</strong></p>
                </div>
              </div>

              @include('partials.log-accordion', ['type' => 'event-audits'])

            </div>
          @endif

        </div>

      </div>

    </div>
  </div>

  </div>
</section>
@endsection

@section('scripts')
@include('includes/gmap')
@endsection
