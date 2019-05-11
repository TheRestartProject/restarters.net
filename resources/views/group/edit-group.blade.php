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
              <li class="breadcrumb-item"><a href="/group">@lang('groups.groups')</a></li>
              <li class="breadcrumb-item"><a href="/group/view/{{{ $formdata->idgroups }}}">{{{ $formdata->name }}}</a></li>
              <li class="breadcrumb-item active" aria-current="page">@lang('groups.edit_group')</li>
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
        @if (\Session::has('error'))
          <div class="alert alert-danger">
              {!! \Session::get('error') !!}
          </div>
        @endif

        <ul class="nav nav-tabs">
          <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#details">Group details</a>
          </li>
          @if( $audits && FixometerHelper::hasRole(Auth::user(), 'Administrator') )
              <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#log">Group log</a>
              </li>
          @endif
        </ul>

        <div class="edit-panel">

          <div class="tab-content">

            <div class="tab-pane active" id="details">

              <form action="/group/edit/{{ $formdata->idgroups }}" method="post" enctype="multipart/form-data">
                @csrf

                <div class="row">
                  <div class="col-lg-6">
                    <h4>@lang('groups.edit_group')</h4>
                    <p>@lang('groups.edit_group_text')</p>
                  </div>
                </div>

                <div class="row">
                  <div class="col-lg-6">
                    <div class="form-group form-group__offset">
                      <label for="grp_name">@lang('groups.groups_name_of'):</label>
                      <input type="text" class="form-control field" id="name" name="name" value="{{ $formdata->name }}" required>
                    </div>
                    <small class="after-offset">@lang('groups.groups_group_small')</small>

                    <div class="form-group form-group__offset">
                      <label for="grp_web">@lang('groups.groups_website'):</label>
                      <input type="url" class="form-control field" id="website" name="website" placeholder="https://" value="{{ $formdata->website }}">
                    </div>
                    <small class="after-offset">@lang('groups.groups_website_small')</small>


                <div class="form-group">
                  <label for="grp_about">@lang('groups.groups_about_group'):</label>
                  <textarea class="form-control rte" rows="6" name="description" id="description">{{ $formdata->free_text }}</textarea>
                </div>
              </div>

              <input type="hidden" name="free_text" id="free_text" value="{{ $formdata->free_text }}">

              <div class="col-lg-6">
                <div class="form-group">
                  <div class="row">

                    <div class="col-12">

                      <div class="row row-compressed">
                        <div class="col-lg-7">
                          <div class="form-group">
                            <label for="autocomplete">@lang('groups.location'):</label>
                            <input type="text" placeholder="Enter your address" id="autocomplete" name="location" class="form-control field field-geolocate" aria-describedby="locationHelpBlock" value="{{ $formdata->location }}" />

                            <small id="locationHelpBlock" class="form-text text-muted">
                              @lang('groups.groups_location_small')
                            </small>
                          </div>
                          <input type="hidden" id="street_number" disabled>
                          <input type="hidden" id="route" disabled>
                          <input type="hidden" id="locality" disabled>
                          <input type="hidden" id="administrative_area_level_1" disabled>
                          <input type="hidden" id="postal_code" disabled>
                          <input type="hidden" id="country" disabled>
                        </div>

                        <div class="col-lg-5">
                          <div id="map-plugin" class="map events__map" data-latitude="{{ $formdata->latitude }}" data-longitude="{{ $formdata->longitude }}" data-zoom="14"></div>
                        </div>
                      </div>

                    </div>
                  </div>

                  <div class="form-group">

            </div>

            <div class="form-group row">
              <div class="col">
                <label for="file">@lang('groups.group_image'):</label><br/>
                <input id="file" name="file" type="file" />
              </div>
            </div>
            <div class="form-group row">
              <div class="col">
                <div class="previews">
                  @if( !empty($images) )
                  @foreach($images as $image)
                  <div id="device-image-{{ $formdata->idgroups }}" class="dz-image">
                    <a href="/uploads/{{ $image->path }}" data-toggle="lightbox">
                    <img src="/uploads/mid_{{ $image->path }}" alt=""></a>
                    <a href="/party/image/delete/{{ $formdata->idgroups }}/{{{ $image->idimages }}}/{{{ $image->path }}}" data-device-id="{{ $formdata->idgroups }}" class="dz-remove ajax-delete-image">Remove file</a>
                  </div>
                  @endforeach
                  @endif
                  <div class="uploads-{{ $formdata->idgroups }}"></div>
                </div>
              </div>
            </div>

            @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') )
            <div class="form-group card p-3">
              <div class="row">
                <div class="col-lg-7">

                  <h4><svg width="18" height="18" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M7.5 1.58a5.941 5.941 0 0 1 5.939 5.938A5.942 5.942 0 0 1 7.5 13.457a5.942 5.942 0 0 1-5.939-5.939A5.941 5.941 0 0 1 7.5 1.58zm0 3.04a2.899 2.899 0 1 1-2.898 2.899A2.9 2.9 0 0 1 7.5 4.62z"/><ellipse cx="6.472" cy=".217" rx=".274" ry=".217"/><ellipse cx="8.528" cy=".217" rx=".274" ry=".217"/><path d="M6.472 0h2.056v1.394H6.472z"/><path d="M8.802.217H6.198l-.274 1.562h3.152L8.802.217z"/><ellipse cx="8.528" cy="14.783" rx=".274" ry=".217"/><ellipse cx="6.472" cy="14.783" rx=".274" ry=".217"/><path d="M6.472 13.606h2.056V15H6.472z"/><path d="M6.198 14.783h2.604l.274-1.562H5.924l.274 1.562zM1.47 2.923c.107-.106.262-.125.347-.04.084.085.066.24-.041.347-.107.107-.262.125-.346.04-.085-.084-.067-.24.04-.347zM2.923 1.47c.107-.107.263-.125.347-.04.085.084.067.239-.04.346-.107.107-.262.125-.347.041-.085-.085-.066-.24.04-.347z"/><path d="M2.923 1.47L1.47 2.923l.986.986 1.453-1.453-.986-.986z"/><path d="M3.27 1.43L1.43 3.27l.91 1.299L4.569 2.34 3.27 1.43zm10.26 10.647c-.107.106-.262.125-.347.04-.084-.085-.066-.24.041-.347.107-.107.262-.125.346-.04.085.084.067.24-.04.347zm-1.453 1.453c-.107.107-.263.125-.347.04-.085-.084-.067-.239.04-.346.107-.107.262-.125.347-.041.085.085.066.24-.04.347z"/><path d="M12.077 13.53l1.453-1.453-.986-.986-1.453 1.453.986.986z"/><path d="M11.73 13.57l1.84-1.84-.91-1.299-2.229 2.229 1.299.91zM0 8.528c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274zm0-2.056c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274z"/><path d="M0 6.472v2.056h1.394V6.472H0z"/><path d="M.217 6.198v2.604l1.562.274V5.924l-1.562.274zM15 6.472c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274zm0 2.056c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274z"/><path d="M15 8.528V6.472h-1.394v2.056H15z"/><path d="M14.783 8.802V6.198l-1.562-.274v3.152l1.562-.274zM2.923 13.53c-.106-.107-.125-.262-.04-.347.085-.084.24-.066.347.041.107.107.125.262.04.346-.084.085-.24.067-.347-.04zM1.47 12.077c-.107-.107-.125-.263-.04-.347.084-.085.239-.067.346.04.107.107.125.262.041.347-.085.085-.24.066-.347-.04z"/><path d="M1.47 12.077l1.453 1.453.986-.986-1.453-1.453-.986.986z"/><path d="M1.43 11.73l1.84 1.84 1.299-.91-2.229-2.229-.91 1.299zM12.077 1.47c.106.107.125.262.04.347-.085.084-.24.066-.347-.041-.107-.107-.125-.262-.04-.346.084-.085.24-.067.347.04zm1.453 1.453c.107.107.125.263.04.347-.084.085-.239.067-.346-.04-.107-.107-.125-.262-.041-.347.085-.085.24-.066.347.04z"/><path d="M13.53 2.923L12.077 1.47l-.986.986 1.453 1.453.986-.986z"/><path d="M13.57 3.27l-1.84-1.84-1.299.91 2.229 2.229.91-1.299z"/></g></svg> @lang('groups.group_admin_only')</h2>


                  <label class="groups-tags-label" for="group_tags[]">@lang('groups.group_tags')</label>
                  <div class="form-control form-control__select">
                    <select id="group_tags[]" name="group_tags[]" class="select2-tags" multiple>
                      @foreach($tags as $tag)
                      @if (in_array($tag->id, $group_tags))
                      <option value="{{ $tag->id }}" selected>{{ $tag->tag_name }}</option>
                      @else
                      <option value="{{ $tag->id }}">{{ $tag->tag_name }}</option>
                      @endif
                      @endforeach
                    </select>
                  </div>

                  <br>

                  <label class="groups-tags-label" for="area">@lang('groups.area')</label>
                  <input type="text" name="area" class="form-control field" value="{{ $formdata->area }}" />
                  <small id="locationHelpBlock" class="form-text text-muted">@lang('groups.helptext_area')</small>

                  @if( is_null($formdata->wordpress_post_id) )
                    <br>
                    <label class="groups-tags-label" for="moderate">@lang('groups.approve_group')</label>
                    <select name="moderate" class="form-control field">
                      <option></option>
                      <option value="approve">Approve</option>
                    </select>
                  @endif

                </div>
              </div>
            </div>
            @endif


          </div>
          <div class="button-group row row-compressed-xs">
            <div class="col-lg-12 d-flex align-items-center justify-content-end">
              <button type="submit" class="btn btn-primary btn-create">@lang('groups.edit_group_save_changes')</button>
            </div>
          </div>

        </div>
      </div>
    </form>

  </div>

  @if( $audits && FixometerHelper::hasRole(Auth::user(), 'Administrator') )

      <div class="tab-pane" id="log">

        <div class="row">
          <div class="col">
            <h4>Group changes</h4>
            <p>Changes made on group <strong>{{ $formdata->name }}</strong></p>
          </div>
        </div>

        @include('partials.log-accordion', ['type' => 'group-audits'])

      </div>

  @endif

</div>

</div>
</div>
</div>
</section>
@endsection

@section('scripts')
@include('includes/gmap')
@endsection
