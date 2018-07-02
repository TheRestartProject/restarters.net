@extends('layouts.app')
@section('content')
<section class="groups">
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="d-flex justify-content-between align-content-center">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">FIXOMETER</a></li>
                <li class="breadcrumb-item"><a href="/group">Groups</a></li>
                <li class="breadcrumb-item active" aria-current="page">@lang('groups.add_groups')</li>
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

          <div class="row">
            <div class="col-lg-6">
              <h4>@lang('groups.add_groups')</h4>
              <p>@lang('groups.add_groups_content')</p>
            </div>
          </div>

          <form action="/group/create" method="post" enctype="multipart/form-data">
              @csrf
            <div class="row">
              <div class="col-lg-6">
                <div class="form-group form-group__offset">
                    <label for="grp_name">@lang('groups.groups_name_of'):</label>
                    <input type="text" class="form-control field" id="grp_name" name="name">
                </div>
                <small class="after-offset">@lang('groups.groups_group_small')</small>

                <div class="form-group form-group__offset">
                    <label for="grp_web">@lang('groups.groups_website'):</label>
                    <input type="url" class="form-control field" id="grp_web" name="website" placeholder="https://">
                </div>
                <small class="after-offset">@lang('groups.groups_website_small')</small>

                <div class="form-group">
                  <label for="grp_about">@lang('groups.groups_about_group'):</label>
                  <!-- <div id="description" class="rte"></div>
                  <noscript> -->
                    <textarea class="form-control rte" rows="6" name="description" id="description"></textarea>
                  <!-- </noscript> -->
                </div>
              </div>

              <input type="hidden" name="free_text" id="free_text" value="">


              <div class="col-lg-6">

                <div class="form-group">
                  <div class="row">

                    <div class="col-12">

                        <div class="row row-compressed">
                            <div class="col-7">
                              <div class="form-group">
                                <label for="autocomplete">@lang('groups.location'):</label>
                                <input type="text" placeholder="Enter your address" id="autocomplete" name="location" class="form-control field field-geolocate" aria-describedby="locationHelpBlock"  />

                                <small id="locationHelpBlock" class="form-text text-muted">
                                  @lang('events.field_venue_helper')
                                </small>

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
                            </div>
                            <div class="col-5">
                              <div id="map-plugin" class="events__map"></div>
                            </div>
                        </div>

                    </div>
                  </div>


                  <div class="form-group">

                      <div class="previews"></div>

                      <label for="file">@lang('groups.group_image'):</label>

                      <!-- <form id="dropzoneSingleEl" class="dropzone" action="/" method="post" enctype="multipart/form-data" data-field1="@lang('groups.field_group_images')" data-field2="@lang('groups.field_group_images_2')"> -->
                          <div class="fallback">
                              <input id="file" name="file" type="file" multiple />
                          </div>
                      <!-- </form> -->



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
