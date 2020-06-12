@extends('layouts.app')
@section('content')
<section class="groups">
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

      <div class="row">
          <div class="col">
              <h1 class="mb-30 mr-30">
                  Add new event
              </h1>
          </div>
      </div>

    <div class="row justify-content-center">
      <div class="col-lg-12">

        @if(isset($response))
          @php( FixometerHelper::printResponse($response) )
        @endif

        <div class="edit-panel">
            {{-- <form action="/party/create" method="post" enctype="multipart/form-data">--}} <!-- id="dropzoneEl" -->
          <form action="/party/create" method="post"> <!-- id="dropzoneEl" -->

          @csrf

          <div class="row">
            <div class="col-lg-6">
              <div class="form-group__offset">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-6">
                  <div class="row">
                    <div class="col-lg-7">
                        <div class="form-group">
                        <label for="event_name">@lang('events.field_event_name'):</label>
                        <input type="text" class="form-control field" id="event_name" name="venue" required placeholder="@lang('events.field_event_name_helper')">
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="form-check" id="online-checkbox-group">
                            <label class="form-check-label">
                                Online event?
                                <input id="online" type="checkbox" value="1" name="online" class="form-check-input" style="position:relative;top:2px">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                </div>

              @if ( $userInChargeOfMultipleGroups )
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="form-group">
                  <label for="event_group">@lang('events.field_event_group'):</label>
                  <div class="form-control form-control__select">
                    <select name="group" id="event_group" class="field field select2" required>
                      <option></option>

                      @if( FixometerHelper::hasRole($user, 'Administrator') )

                        @foreach($allGroups as $group)
                          @if( $group->idgroups == $selected_group_id )
                            <option selected value="{{{ $group->idgroups }}}">{{{ $group->name }}}</option>
                          @else
                            <option value="{{{ $group->idgroups }}}">{{{ $group->name }}}</option>
                          @endif
                        @endforeach

                      @else

                        @foreach($user_groups as $group)
                          @if( $group->idgroups == $selected_group_id )
                            <option selected value="{{{ $group->idgroups }}}">{{{ $group->name }}}</option>
                          @else
                            <option value="{{{ $group->idgroups }}}">{{{ $group->name }}}</option>
                          @endif
                        @endforeach

                      @endif

                    </select>
                  </div>
                        </div></div>
                </div>
              @else
                <input type="hidden" name="group" value="{{ $user_groups[0]->idgroups }}">
              @endif

              <div class="form-group">
                <label for="event_desc">@lang('events.field_event_desc'):</label>
                <div class="rte" name="description" id="description"></div>
              </div>

              <input type="hidden" name="free_text" id="free_text" value="">
            </div>
            <div class="col-lg-6">
              <div class="form-group">
                <div class="row">
                  <div class="col-lg-7">
                    <label for="event_date">@lang('events.field_event_date'):</label>
                    <input type="date" id="event_date" name="event_date" class="form-control field" required>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-lg-7">
                    <div class="form-group">

                      <label for="field_event_time">@lang('events.field_event_time'):</label>

                      <div class="row">

                        <div class="col-6">
                          <input type="time" id="start-time" name="start" class="form-control field" required>
                        </div>

                        <div class="col-6">
                          <label class="sr-only" for="field_event_time_2">@lang('events.field_event_time'):</label>
                          <input type="time" id="end-time" name="end" class="form-control field" required>
                        </div>

                      </div>

                    </div>
                  </div>
                  <div class="col-12">

                      <div class="row">
                          <div class="col-lg-7">
                            <div class="form-group">
                              <label for="autocomplete">@lang('events.field_event_venue'):</label>
                              <input type="text" placeholder="Enter your address" id="autocomplete" name="location" class="form-control field field-geolocate" aria-describedby="locationHelpBlock">

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
                            <div id="map-plugin" class="events__map"></div>
                          </div>
                      </div>

                  </div>
                </div>

              </div>
            </div>
          </div>

          <div class="button-group row">
              <div class="offset-lg-3 col-lg-7 d-flex align-items-right justify-content-end text-right">
                  <span class="button-group__notice">@lang('events.before_submit_text')</span>
              </div>
              <div class="col-lg-2 d-flex align-items-center justify-content-end">
                  <input type="submit" class="btn btn-primary btn-block btn-create" id="create-event" value="@lang('events.create_event')">
              </div>
          </div>

        </form>
        </div>

      </div>
    </div>

  </div>
</section>
@endsection

@section('scripts')
@include('includes/gmap')
@endsection
