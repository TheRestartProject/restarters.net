@extends('layouts.app')
@section('content')
<section class="groups">
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="d-flex justify-content-between align-content-center">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="">FIXOMETER</a></li>
                <li class="breadcrumb-item"><a href="">@lang('events.event')</a></li>
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

        <div class="edit-panel">
          <form action="/party/edit/<?php echo $formdata->id; ?>" method="post" id="party-edit" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" value="<?php echo $formdata->id; ?>" >

          <div class="row">
            <div class="col-lg-6">
              <div class="form-group__offset">
              <h4>@lang('events.edit_event')</h4>
              <p>@lang('events.edit_event_content')</p>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-6">
              <div class="form-group form-group__offset">
                  <label for="event_name">@lang('events.field_event_name'):</label>
                  <input type="text" class="form-control field" id="event_name" name="venue" value="{{ $formdata->venue }}">
              </div>

              <input type="hidden" name="group" id="group" value="<?php echo $formdata->group; ?>">

              <div class="form-group form-group__offset">
                  <label for="event_group">@lang('events.field_event_group'):</label>
                  <div class="form-control form-control__select">
                    <select name="group" id="event_group" class="field field select2">
                      <option></option>
                      @foreach($group_list as $group)
                      <option value="<?php echo $group->id; ?>" <?php echo($group->id == $formdata->group ? 'selected' : ''); ?>><?php echo $group->name; ?></option>
                      @endforeach
                    </select>
                  </div>
              </div>

              <div class="form-group">
                <label for="event_desc">@lang('events.field_event_desc'):</label>
                <!-- <div id="textarea-1" class="rte"></div> -->
                <!-- <noscript>
                  <textarea name="free_text" id="grp_desc">{!! $formdata->free_text !!}</textarea>
                </noscript> -->
                <div class="rte" name="description" id="description">{!! $formdata->free_text !!}</div>
              </div>

              <input type="hidden" name="free_text" id="free_text" value="">
            </div>
            <div class="col-lg-6">
              <div class="form-group">
                <div class="row">
                  <div class="col-7">
                    <label for="event_date">@lang('events.field_event_date'):</label>
                    <input type="date" id="event_date" name="event_date" class="form-control field" value="<?php echo date('Y-m-d', $formdata->event_date); ?>">
                  </div>
                </div>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-7">
                       <div class="form-group">
                    <label for="field_event_time">@lang('events.field_event_time'):</label>
                    <div class="row row-compressed">

                      <div class="col-6">
                      <input type="text" id="field_event_time" name="start" class="form-control field" value="{{ $formdata->start }}">
                      </div>
                      <div class="col-6">
                      <label class="sr-only" for="field_event_time_2">@lang('events.field_event_time'):</label>
                      <input type="text" id="field_event_time_2" name="end" class="form-control field" value="{{ $formdata->end }}">
                      </div>
                      </div>

                    </div>
                  </div>
                  <div class="col-12">

                      <div class="row row-compressed">
                          <div class="col-7">
                            <div class="form-group">
                              <label for="autocomplete">@lang('events.field_event_venue'):</label>
                              <input type="text" placeholder="Enter your address" id="autocomplete" name="location" class="form-control field field-geolocate" aria-describedby="locationHelpBlock" value="{{ $formdata->location }}"/>

                              <small id="locationHelpBlock" class="form-text text-muted">
                                @lang('events.field_venue_helper')
                              </small>

                              <input type="hidden" id="latitude" name="latitude">
                              <input type="hidden" id="longitude" name="longitude">

                            </div>
                            <div class="form-group">
                              <label for="street_number">@lang('events.field_event_street_address'):</label>
                              <input class="form-control field" id="street_number" disabled="true" />
                            </div>
                            <div class="form-group">
                              <label for="route" class="sr-only">@lang('events.field_event_route'):</label>
                              <input class="form-control field" id="route" disabled="true" />
                            </div>
                            <div class="form-group">
                              <label for="locality">@lang('events.field_event_city'):</label>
                              <input class="form-control field" id="locality" disabled="true" />
                            </div>
                            <div class="form-group">
                              <label for="administrative_area_level_1">@lang('events.field_event_county'):</label>
                              <input class="form-control field" id="administrative_area_level_1" disabled="true" />
                            </div>
                            <div class="form-group">
                              <label for="postal_code">@lang('events.field_event_zip'):</label>
                              <input class="form-control field" id="postal_code" disabled="true" />
                            </div>
                            <div class="form-group">
                              <label for="country">@lang('events.field_event_country'):</label>
                              <input class="form-control field" id="country" disabled="true" />
                            </div>
                          </div>
                          <div class="col-5">
                            <div id="map-plugin" class="events__map"></div>
                          </div>
                      </div>

                  </div>
                </div>


                <div class="form-group">

                    <div class="previews"></div>

                    <label for="file">@lang('events.field_add_image'):</label>

                    <!-- <form id="dropzoneEl" class="dropzone" action="/party/add-image/{{ $formdata->id }}" method="post" enctype="multipart/form-data" data-field1="@lang('events.field_event_images')" data-field2="@lang('events.field_event_images_2')"> -->
                        <div class="fallback">
                            <input id="file" name="file[]" type="file" multiple />
                        </div>
                    <!-- </form> -->



                </div>

              </div>

            </div>
          </div>

          <div class="button-group row">
              <div class="offset-lg-4 col-lg-6 d-flex align-items-center justify-content-start">
                  <span class="button-group__notice">@lang('events.before_submit_text')</span>
              </div>
              <div class="col-lg-2 d-flex align-items-center justify-content-end">
                  <input type="submit" class="btn btn-primary btn-create" id="create-event" value="@lang('events.save_event')">
              </div>
          </div>

          </form>
        </div>

      </div>
    </div>

  </div>
</section>
@endsection
