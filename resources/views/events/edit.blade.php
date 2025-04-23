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
            @lang('events.editing', ['event' => '<a style="color:black; text-decoration:underline" href="/party/view/'. $formdata['idevents'] .'">'. $formdata['venue'] .'</a>'])
        </h1>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-12">
        @if(isset($response))
          @php( App\Helpers\Fixometer::printResponse($response, false) )
        @endif

        <ul class="nav nav-tabs">
          <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#details">@lang('events.event_details')</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#photos">@lang('events.event_photos')</a>
          </li>
          @if( $audits && App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') )
          <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#log">@lang('events.event_log')</a>
          </li>
          @endif
        </ul>

        <div class="edit-panel">

          <div class="tab-content">

            <div class="tab-pane active" id="details">

              <div class="row">
                <div class="col-lg-12">
                  <div class="form-group__offset">
                  <!-- <p>@lang('events.edit_event_content')</p>-->
                  </div>
                </div>
              </div>

              <div class="vue">
                <EventAddEdit
                    csrf="{{ csrf_token() }}"
                    :idevents="{{ $formdata['idevents'] }}"
                    @if( App\Helpers\Fixometer::hasRole($user, 'Administrator') )
                    :groups="{{ json_encode($allGroups, JSON_INVALID_UTF8_IGNORE) }}"
                    @else
                    :groups="{{ json_encode($user_groups, JSON_INVALID_UTF8_IGNORE) }}"
                    @endif
                    :can-approve="{{ (App\Helpers\Fixometer::hasRole( Auth::user(), 'Administrator') || Auth::user()->isCoordinatorForGroup(App\Models\Group::find($selected_group_id))) ? "true" : "false" }}"
                />
              </div>
          </div>

          <div class="tab-pane" id="photos">
            <div class="form-group row">

                <div class="col-6 col-lg-6">
                  <label for="file">@lang('events.field_add_image'):</label>
                  <form id="dropzoneEl-{{ $formdata['idevents'] }}" data-deviceid="{{ $formdata['idevents'] }}" class="dropzone dropzoneEl" action="/party/image-upload/{{ $formdata['idevents'] }}" method="post" enctype="multipart/form-data" data-field1="@lang('events.field_event_images')" data-field2="@lang('events.field_event_images_2')">
                      @csrf
                      <div class="dz-default dz-message"></div>
                      <div class="fallback">
                          <input id="file-{{ $formdata['idevents'] }}" name="file-{{ $formdata['idevents'] }}" type="file" multiple />
                      </div>
                  </form>
                </div>

                <div class="col-6 col-lg-6">
                  <div class="previews">
                    @if( !empty($images) )
                      @foreach($images as $image)
                        <div id="device-image-{{ $formdata['idevents'] }}" class="dz-image">
                          <a href="/uploads/{{ $image->path }}" data-toggle="lightbox">
                          <img src="/uploads/thumbnail_{{ $image->path }}" alt="placeholder"></a>
                          <a href="/party/image/delete/{{ $formdata['idevents'] }}/{{{ $image->idimages }}}/{{{ $image->path }}}" data-device-id="{{ $formdata['idevents'] }}" class="dz-remove ajax-delete-image">Remove file</a>
                        </div>
                      @endforeach
                    @endif
                    <div class="uploads-{{ $formdata['idevents'] }}"></div>
                  </div>
                </div>

            </div>

          </div>

          @if( $audits && App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') )
            <div class="tab-pane" id="log">

              <div class="row">
                <div class="col">
                  <h4>Event changes</h4>
                  <p>Changes made on event <strong>{{ $formdata['venue'] }}</strong></p>
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
