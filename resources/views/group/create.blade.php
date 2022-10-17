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

    <div class="row justify-content-center">
      <div class="col-lg-12">

        <div class="vue">
          <GroupAddEditPage />
        </div>
        @if(isset($response))
          @php( App\Helpers\Fixometer::printResponse($response) )
        @endif

        <div class="edit-panel">

          <div class="row">
            <div class="col">
              <p>@lang('groups.add_groups_content')</p>
            </div>
          </div>

          <form action="/group/create" method="post" enctype="multipart/form-data">
              @csrf
            <div class="row">
              <div class="col-lg-6">
                <div class="form-group form-group__offset">
                    <label for="grp_name">@lang('groups.groups_name_of'):</label>
                    <input type="text" class="form-control field" id="grp_name" name="name" required>
                </div>
                <small class="after-offset">@lang('groups.groups_group_small')</small>

                <div class="form-group form-group__offset">
                    <label for="grp_web">@lang('groups.groups_website'):</label>
                    <input type="url" class="form-control field" id="grp_web" name="website" placeholder="https://">
                </div>
                <small class="after-offset">@lang('groups.groups_website_small')</small>

                <div class="form-group mt-4">
                  <label for="grp_about">@lang('groups.groups_about_group'):</label>
                  <div class="vue">
                    <RichTextEditor name="free_text" class="mb-2" />
                  </div>
                </div>
              </div>

              <div class="col-lg-6">

                <div class="form-group">
                  <div class="row">

                    <div class="col-12">

                        <div class="row row-compressed mb-1">
                            <div class="col-lg-7">
                              <div class="form-group mb-1">
                                <label for="autocomplete">@lang('groups.location'):</label>
                                <input type="text" placeholder="@lang('groups.groups_location_placeholder')" id="autocomplete" name="location" class="form-control field field-geolocate" aria-describedby="locationHelpBlock"  />

                                <small id="locationHelpBlock" class="form-text text-muted">
                                  @lang('groups.groups_location_small')
                                </small>

                                <div class="vue">
                                  <GroupTimeZone />
                                </div>
                              </div>

                              <div style="position: absolute; left: -10000px; top: -10000px;">

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

                              <div class="form-group">
                                <label for="phone">@lang('groups.field_phone'):</label>
                                <input class="form-control field" id="phone" name="phone" type="tel" aria-describedby="phoneHelpBlock" />

                                <small id="phoneHelpBlock" class="form-text text-muted">
                                  @lang('groups.phone_small')
                                </small>
                              </div>

                            </div>
                            <div class="col-lg-5">
                              <div id="map-plugin" class="events__map"></div>
                            </div>
                        </div>

                    </div>
                  </div>

                  <div class="row">

                    <div class="col-12">

                      <div class="row row-compressed mb-1">

                        <div class="form-group">

                          <div class="previews"></div>

                          <label for="file">@lang('groups.group_image'):</label>

                          <div class="fallback">
                              <input id="file" name="file" type="file" />
                          </div>

                        </div>

                      </div>

                    </div>

                  </div>

                </div>

                <div class="button-group row row-compressed-xs">
                    <div class="col-lg-8 d-flex align-items-center justify-content-start">
                        <span class="button-group__notice">@lang('groups.groups_approval_text')</span>
                    </div>
                    <div class="col-lg-4 d-flex align-items-center justify-content-end">
                        <button type="submit" class="btn btn-primary btn-create">@lang('groups.create_group')</button>
                    </div>
                </div>
              </form>

              </div>

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