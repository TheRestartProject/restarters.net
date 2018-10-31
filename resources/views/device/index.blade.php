@extends('layouts.app')

@section('title')
    Devices
@endsection

@section('content')
<section class="devices">
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="d-md-flex justify-content-between align-content-center">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">FIXOMETER</a></li>
              <li class="breadcrumb-item active" aria-current="page">@lang('devices.devices')</li>
            </ol>
          </nav>

          <div class="btn-group button-group-filters">
            <button class="reveal-filters btn btn-secondary d-lg-none d-xl-none" type="button" data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter">Reveal filters</button>
            <a href="/export/devices/?{{{ Request::getQueryString() }}}" class="btn btn-primary btn-save"><i class="fa fa-download"></i>@lang('devices.export_device_data')</a>
          </div>

        </div>
      </div>
    </div>

    <div class="row justify-content-center">

        <div class="col-lg-3">


            <div class="collapse d-lg-block d-xl-block fixed-overlay-md" id="collapseFilter">

              <form action="/device/search/" method="get">
                <div class="form-row">
                    <div class="form-group col mobile-search-bar-md">
                        <button class="btn btn-primary btn-groups" type="submit">@lang('devices.search_all_devices')</button>
                        <button type="button" class="d-lg-none mobile-search-bar-md__close" data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter"><svg width="21" height="21" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Close</title><g><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></svg></button>
                    </div>
                </div>

                <aside class="edit-panel edit-panel__side">
                    <legend>@lang('devices.by_taxonomy')</legend>
                    <div class="form-group">
                        <label for="items_cat">@lang('devices.category'):</label>
                        <div class="form-control form-control__select">
                            <select id="categories" name="categories[]" class="form-control select2-tags" multiple title="Choose categories...">
                                @if(isset($categories))
                                @foreach($categories as $cluster)
                                    <optgroup label="<?php echo $cluster->name; ?>">
                                    @foreach($cluster->categories as $c)
                                        @if (!empty($selected_categories) && in_array($c->idcategories, $selected_categories))
                                          <option value="<?php echo $c->idcategories; ?>" selected><?php echo $c->name; ?></option>
                                        @else
                                          <option value="<?php echo $c->idcategories; ?>"><?php echo $c->name; ?></option>
                                        @endif
                                    @endforeach
                                    </optgroup>
                                @endforeach
                                @endif
                                <option value="46">Misc</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="items_group">@lang('devices.group'):</label>
                        <div class="form-control form-control__select">
                        <select id="groups" name="groups[]" class="form-control select2-tags" multiple data-live-search="true" title="Choose groups...">
                            @if(isset($groups))
                              @foreach($groups as $g)
                                @if (!empty($selected_groups) && in_array($g->idgroups, $selected_groups))
                                  <option value="<?php echo $g->idgroups; ?>" selected><?php echo $g->name; ?></option>
                                @else
                                  <option value="<?php echo $g->idgroups; ?>"><?php echo $g->name; ?></option>
                                @endif
                              @endforeach
                            @endif
                        </select>
                        </div>
                    </div>

                </aside>

                <aside class="edit-panel edit-panel__side">
                    <legend>@lang('devices.by_date')</legend>
                    <div class="form-group">
                        <!-- <div class="input-group date from-date"> -->
                        <label for="from-date">@lang('devices.from_date'):</label>
                        <input type="date" class="field form-control" id="search-from-date" name="from-date" value="{{ $from_date }}" >
                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                        <!-- </div> -->
                    </div>
                    <div class="form-group">
                        <!-- <div class="input-group date to-date"> -->
                        <label for="to-date">@lang('devices.to_date'):</label>
                        <input type="date" class="field form-control" id="search-to-date" name="to-date" value="{{ $to_date }}" >
                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                        <!-- </div> -->
                    </div>

                </aside>

                <aside class="edit-panel edit-panel__side">
                    <legend>@lang('devices.various')</legend>
                    <div class="form-group">
                        <label for="device_id">@lang('devices.device_id'):</label>
                        <input type="text" class="form-control field" id="device_id" name="device_id" placeholder="Device Id..."  value="{{ $device_id }}" >
                    </div>
                    <div class="form-group">
                        <label for="brand">@lang('devices.device_brand'):</label>
                        <input type="text" class="form-control field" id="brand" name="brand" placeholder="Brand..." value="{{ $brand }}" >
                    </div>
                    <div class="form-group">
                        <label for="model">@lang('devices.device_model'):</label>
                        <input type="text" class="form-control field" id="model" name="model" placeholder="Model..." value="{{ $model }}" >
                    </div>
                    <div class="form-group">
                        <label for="problem">@lang('devices.search_comments'):</label>
                        <input type="text" class="form-control field" id="problem" name="problem" placeholder="Search in the comment..."  value="{{ $problem }}" >
                    </div>

                </aside>
              </form>
            </div><!-- /collapseFilter -->
        </div>

        <div class="col-lg-9">

            <br>

            <div class="row">
                <div class="col-12">

                    <div class="d-flex flex-row align-content-center justify-content-end">


                        <div class="btn-group btn-group__duo" role="group" aria-label="Filter options">

                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="sr-only">Items</span><svg width="14" height="12" viewBox="0 0 12 10" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M3.163.324A.324.324 0 0 0 2.84 0H.324A.324.324 0 0 0 0 .324v1.909c0 .179.145.324.324.324H2.84a.324.324 0 0 0 .323-.324V.324zm0 3.715a.324.324 0 0 0-.323-.324H.324A.324.324 0 0 0 0 4.039v1.91c0 .178.145.323.324.323H2.84a.323.323 0 0 0 .323-.323v-1.91zm0 3.715a.323.323 0 0 0-.323-.323H.324A.324.324 0 0 0 0 7.754v1.91c0 .179.145.324.324.324H2.84a.324.324 0 0 0 .323-.324v-1.91zM11.25.324A.324.324 0 0 0 10.926 0h-6.37a.323.323 0 0 0-.323.324v1.909c0 .179.144.324.323.324h6.37a.324.324 0 0 0 .324-.324V.324zm0 3.715a.324.324 0 0 0-.324-.324h-6.37a.323.323 0 0 0-.323.324v1.91c0 .178.144.323.323.323h6.37a.324.324 0 0 0 .324-.323v-1.91zm0 3.715a.324.324 0 0 0-.324-.323h-6.37a.323.323 0 0 0-.323.323v1.91c0 .179.144.324.323.324h6.37a.324.324 0 0 0 .324-.324v-1.91z" fill="#fff"/></svg>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu2">
                                    <label class="dropdown-item">
                                      <input class="filter-columns" data-id="deviceID" type="checkbox" value="1" class="dropdown-item-checkbox" checked> #</input>
                                    </label>
                                    <label class="dropdown-item">
                                      <input class="filter-columns" data-id="state" type="checkbox" value="1" class="dropdown-item-checkbox" checked> State</input>
                                    </label>
                                    <label class="dropdown-item">
                                      <input class="filter-columns" data-id="category" type="checkbox" value="1" class="dropdown-item-checkbox" checked> Category</input>
                                    </label>
                                    <label class="dropdown-item">
                                      <input class="filter-columns" data-id="brand" type="checkbox" value="1" class="dropdown-item-checkbox" checked> Brand</input>
                                    </label>
                                    <label class="dropdown-item">
                                      <input class="filter-columns" data-id="model" type="checkbox" value="1" class="dropdown-item-checkbox" checked> Model</input>
                                    </label>
                                    <label class="dropdown-item">
                                      <input class="filter-columns" data-id="comment" type="checkbox" value="1" class="dropdown-item-checkbox" checked> Comment</input>
                                    </label>
                                    <label class="dropdown-item">
                                      <input class="filter-columns" data-id="eventGroup" type="checkbox" value="1" class="dropdown-item-checkbox" checked> Event (Group)</input>
                                    </label>
                                    <label class="dropdown-item">
                                      <input class="filter-columns" data-id="eventDate" type="checkbox" value="1" class="dropdown-item-checkbox" checked> Event Date</input>
                                    </label>
                                    <label class="dropdown-item">
                                      <input class="filter-columns" data-id="location" type="checkbox" value="1" class="dropdown-item-checkbox" checked> Location</input>
                                    </label>
                                </div>
                            </div>


                        </div>


                    <div>

                </div>
            </div>

            <br>

            <div class="table-responsive" id="sort-table">
                <table class="table table-hover table-responsive table-striped bootg table-devices sortable" id="devices-table">
                    <thead>
                        <tr>
                            <th scope="col"></th> <!-- <th scope="col" data-column-id="comment">Comment</th> -->
                            <th scope="col"></th>
                            <th scope="col" class="deviceID" data-header-css-class="comm-cell" data-identifier="true" data-type="numeric">#</th> <!-- <th scope="col" data-column-id="edit" data-header-css-class="comm-cell" data-formatter="editLink" data-sortable="false">edit</th> -->
                            <th scope="col" class="state" data-header-css-class="mid-cell" data-formatter="statusBox">@lang('devices.state')</th>
                            <th scope="col" class="category">@lang('devices.category')</th>
                            <th scope="col" class="brand">@lang('devices.brand')</th>
                            <th scope="col" class="model">@lang('devices.model')</th>
                            <th scope="col" class="comment">@lang('devices.comment')</th>
                            <th scope="col" class="eventGroup">@lang('devices.eventgroup')</th>
                            <th scope="col" class="eventDate" data-header-css-class="mid-cell">@lang('devices.eventdate')</th>
                            <th scope="col" class="location">@lang('devices.location')</th> <!-- <th scope="col" data-column-id="groupName">Event (Group)</th> -->

                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($list))
                            @foreach($list as $device)
                              <tr>
                                @include('partials/device-comment-photo', ['comment' => $device->problem ])
                                <td class="deviceID"><a href="/device/page-edit/<?php echo $device->id; ?>"><?php echo $device->id; ?></a></td>
                                @include('partials/device-status', ['status' => $device->repair_status])
                                <td class="category"><?php echo $device->category_name; ?></td>
                                <td class="brand"><?php echo $device->brand; ?></td>
                                <td class="model"><?php echo $device->model; ?></td>
                                <td class="comment"><?php echo $device->problem; ?></td>
                                <td class="eventGroup"><?php echo $device->group_name; ?></td>
                                <td class="eventDate"><?php echo strftime('%Y-%m-%d', $device->event_date); ?></td>
                                <td class="location"><?php echo $device->event_location; ?></td>
                              </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <br>

            <div class="d-flex justify-content-center">
                <nav aria-label="Page navigation example">
                <ul class="pagination">
                  @if (!empty($_GET))
                    {!! $list->appends(['categories' => $selected_categories, 'groups' => $selected_groups, 'from_date' => $from_date, 'to-date' => $to_date, 'device_id' => $device_id, 'brand' => $brand, 'model' => $model, 'problem' => $problem])->links() !!}
                  @else
                    {!! $list->links() !!}
                  @endif
                </ul>
                </nav>
            </div>

        </div>
    </div>

  </div>
</section>
@endsection
