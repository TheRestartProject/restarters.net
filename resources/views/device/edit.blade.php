@extends('layouts.app')

@section('content')

<section class="devices">
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="d-flex justify-content-between align-content-center">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">FIXOMETER</a></li>
              <li class="breadcrumb-item"><a href="{{ route('devices') }}">@lang('devices.devices')</a></li>
              <li class="breadcrumb-item active" aria-current="page">@lang('devices.edit_devices')</li>
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
            <a class="nav-link active" data-toggle="tab" href="#details">Device details</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#photos">Device photos</a>
          </li>
          @if( $audits && FixometerHelper::hasRole(Auth::user(), 'Administrator') )
              <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#log">Device log</a>
              </li>
          @endif
        </ul>

        <div class="edit-panel">

            <div class="tab-content">

              <div class="tab-pane active" id="details">

                <h2>@lang('devices.edit_devices_details')</h2>

                <form action="/device/page-edit/<?php echo $formdata->iddevices; ?>" method="post" enctype="multipart/form-data">
                  @csrf
                  <div class="row">
                      <div class="col-lg-4">

                          <div class="form-group <?php if(isset($error) && isset($error['event']) && !empty($error['event'])) { echo "has-error"; } ?>">
                              <label for="event">Restart Party:</label>
                              <div class="form-control form-control__select">
                                  <select id="event" name="event" class="form-control field select2">
                                      <option></option>
                                      @if(isset($events))
                                        <?php foreach($events as $event){ ?>
                                        <option value="<?php echo $event->id; ?>"<?php echo ($event->id == $formdata->event ? ' selected' : ''); ?>><?php echo $event->location . ' [' . date('d/m/Y', $event->event_timestamp) . ']'; ?></option>
                                        <?php } ?>
                                      @endif
                                  </select>
                              </div>
                              <?php if(isset($error) && isset($error['event']) && !empty($error['event'])) { echo '<span class="help-block text-danger">' . $error['event'] . '</span>'; } ?>
                          </div>

                          <div class="form-group <?php if(isset($error) && isset($error['category']) && !empty($error['category'])) { echo "has-error"; } ?>">

                              <label for="category">Category:</label>
                              <div class="form-control form-control__select">
                                  <select id="category" name="category" class="form-control category field select2">
                                      <option></option>
                                      @if(isset($categories))
                                        <?php foreach($categories as $category){ ?>
                                        <option value="<?php echo $category->idcategories; ?>"<?php echo ($category->idcategories == $formdata->category ? ' selected' : ''); ?>><?php echo $category->name; ?></option>
                                        <?php } ?>
                                      @endif
                                  </select>
                              </div>
                              <?php if(isset($error) && isset($error['category']) && !empty($error['category'])) { echo '<span class="help-block text-danger">' . $error['category'] . '</span>'; } ?>
                          </div>

                          @if( $formdata->category == 46 )
                            <div class="form-group <?php if(isset($error) && isset($error['weight']) && !empty($error['weight'])) { echo "has-error"; } ?>">
                              <label for="category">Weight:</label>
                              <div class="display-weight pt-1 mb-2">
                                  <div class="input-group">
                                    <input type="number" class="form-control field weight" id="weight" name="weight" min="0.01" step=".01" placeholder="@lang('partials.est_weight')" autocomplete="off" value="{{ $formdata->estimate }}">
                                    <div class="input-group-append">
                                      <span class="input-group-text">kg</span>
                                    </div>
                                  </div>
                              </div>
                            </div>
                          @endif

                          <div class="form-group">
                              <label for="brand">@lang('devices.brand'):</label>
                              <div class="form-control form-control__select">
                                  <select name="brand" class="select2-with-input" id="brand">
                                      @php($i = 1)
                                      @if( empty($device->brand) )
                                        <option value="" selected></option>
                                      @else
                                        <option value=""></option>
                                      @endif
                                      @foreach($brands as $brand)
                                        @if ($formdata->brand == $brand->brand_name)
                                          <option value="{{ $brand->brand_name }}" selected>{{ $brand->brand_name }}</option>
                                          @php($i++)
                                        @else
                                          <option value="{{ $brand->brand_name }}">{{ $brand->brand_name }}</option>
                                        @endif
                                      @endforeach
                                      @if( $i == 1 && !empty($formdata->brand) )
                                        <option value="{{ $formdata->brand }}" selected>{{ $formdata->brand }}</option>
                                      @endif
                                  </select>
                              </div>
                          </div>
                          <div class="form-group">
                              <label for="model">@lang('devices.model'):</label>
                              <input type="text" name="model" id="model" class="form-control field" value="<?php echo $formdata->model; ?>">
                          </div>
                          <div class="form-group">
                              <label for="age">@lang('devices.age'):</label>
                              <input type="text" name="age" id="age" class="form-control field" value="<?php echo $formdata->age; ?>">
                          </div>
                          <!-- <div class="form-group">
                              <label for="profilePhoto">Upload Device Photo:</label>
                              <input type="file" class="form-control" id="devicePhoto" name="devicePhoto">
                          </div> -->



                      </div>
                      <div class="offset-lg-1 col-lg-7">
                          <div class="row">
                              <div class="col-4 col-lg-auto flex-column d-flex d-lg-table-cell">
                                  <div class="form-group">
                                      <label for="repair_status">@lang('devices.repair_status'):</label>
                                      <div class="form-control form-control__select">
                                          <select name="repair_status" id="repair_status" class="form-control field select2 repair-status">
                                            <option value="0">@lang('general.please_select')</option>
                                            <option value="1" <?php echo ($formdata->repair_status == 1 ? ' selected' : ''); ?>>Fixed</option>
                                            <option value="2" <?php echo ($formdata->repair_status == 2 ? ' selected' : ''); ?>>Repairable</option>
                                            <option value="3" <?php echo ($formdata->repair_status == 3 ? ' selected' : ''); ?>>End-of-life</option>
                                          </select>
                                      </div>
                                      <?php if(isset($error) && isset($error['repair_status']) && !empty($error['repair_status'])) { echo '<span class="help-block text-danger">' . $error['repair_status'] . '</span>'; } ?>
                                  </div>
                              </div>
                              <div class="col-4 col-device <?php echo ($formdata->repair_status == 2 ? 'col-device-auto' : 'd-none'); ?>">
                                  <div class="form-group">
                                      <label for="repair_status_2">@lang('devices.repair_details'):</label>
                                      <div class="form-control form-control__select">
                                          <select name="repair_more" id="repair_details_edit" class="form-control field select2 repair-details-edit">
                                            <option value="0">@lang('general.please_select')</option>
                                            <option value="1" <?php echo ($formdata->more_time_needed == 1 ? ' selected' : '') ?>>More time needed</option>
                                            <option value="2" <?php echo ($formdata->professional_help == 1 ? ' selected' : '') ?>>Professional help</option>
                                            <option value="3" <?php echo ($formdata->do_it_yourself == 1 ? ' selected' : '') ?>>Do it yourself</option>
                                          </select>
                                      </div>
                                  </div>
                              </div>
                              <div class="col-4 col-device <?php echo ($formdata->repair_status == 1 || $formdata->repair_status == 2 ? 'col-device-auto' : 'd-none'); ?>">
                                  <div class="form-group">
                                      <label for="spare_parts">@lang('devices.spare_parts_required'):</label>
                                      <div class="form-control form-control__select">
                                          <select name="spare_parts" id="spare_parts" class="form-control field select2 spare-parts">
                                            <option @if ( $formdata->spare_parts == 1 && is_null($formdata->parts_provider) ) value="4" @else value="0" @endif>@lang('general.please_select')</option>
                                            <option value="1" @if ( $formdata->spare_parts == 1 && !is_null($formdata->parts_provider) ) selected @endif>@lang('partials.yes_manufacturer')</option>
                                            <option value="3" @if ( $formdata->parts_provider == 2 ) selected @endif>@lang('partials.yes_third_party')</option>
                                            <option value="2" @if ( $formdata->spare_parts == 2 ) selected @endif>@lang('partials.no')</option>
                                          </select>
                                      </div>
                                  </div>
                              </div>
                              <div class="col-4 col-device <?php echo ($formdata->repair_status == 3 ? 'col-device-auto' : 'd-none'); ?>">
                                  <div class="form-group">
                                      <label for="repair_barrier">@lang('devices.repair_barrier'):</label>
                                      <div class="form-control form-control__select form-control__select_placeholder">
                                          <select name="barrier[]" multiple id="repair_barrier" class="form-control field select2-repair-barrier repair-barrier">
                                            @foreach( FixometerHelper::allBarriers() as $barrier )
                                              <option value="{{{ $barrier->id }}}" @if ( $formdata->barriers->contains($barrier->id) ) selected @endif>{{{ $barrier->barrier }}}</option>
                                            @endforeach
                                          </select>
                                      </div>
                                  </div>
                              </div>
                          </div>

                          <div class="form-group">
                              <label for="problem">@lang('devices.devices_description'):</label>
                              <textarea class="form-control rte" name="problem" id="problem"><?php echo $formdata->problem; ?></textarea>
                          </div>

                          <div class="form-check d-flex align-items-center justify-content-start">
                              @if ($formdata->wiki == 1)
                                <input class="form-check-input" type="checkbox" name="wiki" id="opt" value="1" checked>
                              @else
                                <input class="form-check-input" type="checkbox" name="wiki" id="opt" value="1">
                              @endif
                              <label class="form-check-label" for="opt">@lang('devices.admin_device')</label>
                          </div>

                          @include('partials.useful-repair-urls', ['urls' => $formdata->urls, 'device' => $formdata])

                          <div class="button-group row">
                              <div class="col-lg-12 d-flex align-items-center justify-content-end">
                                  <button type="submit" class="btn btn-primary btn-create">@lang('devices.save_device')</button>
                              </div>
                          </div>


                      </div>
                  </div>
                </form>

            </div>

            <div class="tab-pane" id="photos">
              <div class="form-group row">

                  <div class="col-6 col-lg-6">
                    <label for="file">@lang('events.field_add_image'):</label>
                    <form id="dropzoneEl-{{ $formdata->iddevices }}" data-deviceid="{{ $formdata->iddevices }}" class="dropzone dropzoneEl" action="/device/image-upload/{{ $formdata->iddevices }}" method="post" enctype="multipart/form-data" data-field1="@lang('devices.field_device_images')" data-field2="@lang('devices.field_device_images_2')">
                        @csrf
                        <div class="dz-default dz-message"></div>
                        <div class="fallback">
                            <input id="file-{{ $formdata->iddevices }}" name="file-{{ $formdata->iddevices }}" type="file" multiple />
                        </div>
                    </form>
                  </div>

                  <div class="col-6 col-lg-6">
                    <div class="previews">
                      @if( !empty($images) )
                        @foreach($images as $image)
                          <div id="device-image-{{ $formdata->iddevices }}" class="dz-image">
                            <a href="/uploads/{{ $image->path }}" data-toggle="lightbox">
                            <img src="/uploads/thumbnail_{{ $image->path }}" alt="placeholder"></a>
                            <a href="/device/image/delete/{{ $formdata->iddevices }}/{{{ $image->idimages }}}/{{{ $image->path }}}" data-device-id="{{ $formdata->iddevices }}" class="dz-remove ajax-delete-image">Remove file</a>
                          </div>
                        @endforeach
                      @endif
                      <div class="uploads-{{ $formdata->iddevices }}"></div>
                    </div>
                  </div>

              </div>

            </div>

            @if( $audits && FixometerHelper::hasRole(Auth::user(), 'Administrator') )
                <div class="tab-pane" id="log">

                  <div class="row">
                    <div class="col">
                      <h4>Device changes</h4>
                      <p>Changes made on device <strong>{{ $formdata->name }}</strong></p>
                    </div>
                  </div>

                  @include('partials.log-accordion', ['type' => 'device-audits'])

                </div>
            @endif

          </div>
        </div><!-- /edit-panel -->
    </div>
  </div>


    <br>

    @if (FixometerHelper::hasRole($user, 'Administrator') || $is_host)
      <div class="alert alert-danger" role="alert">

            <div class="row">
              <div class="col-md-8 col-lg-9 d-flex flex-column align-content-center"><strong>@lang('devices.delete_device_content')</strong></div>
              <div class="col-md-4 col-lg-3 d-flex flex-column align-content-center">
                <a href="" class="btn btn-danger">@lang('devices.delete_device')</a>
              </div>
            </div>

      </div>
    @endif


  </div>
</section>

  <!-- <div class="form-group">
      <label>Repair Status:</label>
      <div class="radio">
          <label>
              <input type="radio" name="repair_status" id="repair_status_1" value="1" <?php //echo ($formdata->repair_status == 1 ? ' checked' : ''); ?>> Fixed
          </label>
      </div>
      <div class="radio">
          <label>
              <input type="radio" name="repair_status" id="repair_status_2" value="2" <?php //echo ($formdata->repair_status == 2 ? ' checked' : ''); ?>> Repairable
          </label>
      </div>
      <div id="repairable-details">
          <div class="checkbox">
              <label>
                  <input type="checkbox" name="more_time_needed" id="more_time_needed" value="1" <?php //echo ($formdata->repair_status == 3 ? ' checked' : ''); ?>> More time needed
              </label>
          </div>
          <div class="checkbox">
              <label>
                  <input type="checkbox" name="professional_help" id="professional_help" value="1"  <?php //echo ($formdata->professioanl_help == 1 ? ' checked' : ''); ?>> Professional help
              </label>
          </div>
          <div class="checkbox">
              <label>
                  <input type="checkbox" name="do_it_yourself" id="do_it_yourself" value="1" <?php //echo ($formdata->do_it_yourself == 1 ? ' checked' : ''); ?>> Do it yourself
              </label>
          </div>
      </div>
      <div class="radio">
          <label>
              <input type="radio" name="repair_status" id="repair_status_3" value="3" <?php //echo ($formdata->end_of_lifecycle == 1 ? ' checked' : ''); ?>> End-of-life
          </label>
      </div>
      <?php //if(isset($error) && isset($error['repair_status']) && !empty($error['repair_status'])) { echo '<span class="help-block text-danger">' . $error['repair_status'] . '</span>'; } ?>
  </div> -->

@endsection
