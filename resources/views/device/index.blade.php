@extends('layouts.app')

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
            <a href="/export/devices" class="btn btn-primary btn-save"><i class="fa fa-download"></i>@lang('devices.export_device_data')</a>
          </div>

        </div>
      </div>
    </div>

    <div class="row justify-content-center">

        <div class="col-lg-3">


            <div class="collapse d-lg-block d-xl-block fixed-overlay" id="collapseFilter">

              <form action="/device/search" method="get">
                <div class="form-row">
                    <div class="form-group col mobile-search-bar">
                        <button class="btn btn-primary btn-groups" type="submit">@lang('devices.search_all_devices')</button>
                        <button type="button" class="d--lg-none d-xl-none d-md-none mobile-search-bar__close" data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter"><svg width="21" height="21" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Close</title><g><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></svg></button>
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

                        <form action="/device/search" class="search field-form-input form-control" method="get">
                          <input type="hidden" name="fltr" value="<?php echo bin2hex(openssl_random_pseudo_bytes(8)); ?>">
                            <label for="search" class="sr-only">Search</label>
                        <input placeholder="Quick search" id="search" type="search"><button type="submit"><span class="sr-only">Search</span><svg width="15" height="15" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M3.394 3.394a4.801 4.801 0 0 1 6.787 0 4.801 4.801 0 0 1 0 6.787 4.801 4.801 0 0 1-6.787 0 4.801 4.801 0 0 1 0-6.787zm1.094 1.094a3.252 3.252 0 1 1 4.599 4.599 3.252 3.252 0 0 1-4.599-4.599z"/><path d="M8.855 10.218l1.363-1.363 2.622 2.622a.964.964 0 0 1-1.363 1.363l-2.622-2.622z"/></svg></button></form>


                        <div class="btn-group btn-group__duo" role="group" aria-label="Filter options">

                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">10</button>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu1">
                                    <a class="dropdown-item" href="#">Action</a>
                                    <a class="dropdown-item" href="#">Another action</a>
                                    <a class="dropdown-item" href="#">Something else here</a>
                                </div>
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="sr-only">Items</span><svg width="14" height="12" viewBox="0 0 12 10" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M3.163.324A.324.324 0 0 0 2.84 0H.324A.324.324 0 0 0 0 .324v1.909c0 .179.145.324.324.324H2.84a.324.324 0 0 0 .323-.324V.324zm0 3.715a.324.324 0 0 0-.323-.324H.324A.324.324 0 0 0 0 4.039v1.91c0 .178.145.323.324.323H2.84a.323.323 0 0 0 .323-.323v-1.91zm0 3.715a.323.323 0 0 0-.323-.323H.324A.324.324 0 0 0 0 7.754v1.91c0 .179.145.324.324.324H2.84a.324.324 0 0 0 .323-.324v-1.91zM11.25.324A.324.324 0 0 0 10.926 0h-6.37a.323.323 0 0 0-.323.324v1.909c0 .179.144.324.323.324h6.37a.324.324 0 0 0 .324-.324V.324zm0 3.715a.324.324 0 0 0-.324-.324h-6.37a.323.323 0 0 0-.323.324v1.91c0 .178.144.323.323.323h6.37a.324.324 0 0 0 .324-.323v-1.91zm0 3.715a.324.324 0 0 0-.324-.323h-6.37a.323.323 0 0 0-.323.323v1.91c0 .179.144.324.323.324h6.37a.324.324 0 0 0 .324-.324v-1.91z" fill="#fff"/></svg>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu2">
                                    <a class="dropdown-item" href="#">Action</a>
                                    <a class="dropdown-item" href="#">Another action</a>
                                    <a class="dropdown-item" href="#">Something else here</a>
                                </div>
                            </div>


                        </div>


                    <div>

                </div>
            </div>

            <br>

            <div class="table-responsive">
                <table class="table table-hover table-responsive table-striped bootg table-devices" id="devices-table">
                    <thead>
                        <tr>
                            <th scope="col"></th> <!-- <th scope="col" data-column-id="comment">Comment</th> -->
                            <th scope="col"></th>
                            <th scope="col" data-column-id="deviceID"  data-header-css-class="comm-cell" data-identifier="true" data-type="numeric">#</th> <!-- <th scope="col" data-column-id="edit" data-header-css-class="comm-cell" data-formatter="editLink" data-sortable="false">edit</th> -->
                            <th scope="col" data-column-id="repairstatus" data-header-css-class="mid-cell" data-formatter="statusBox">@lang('devices.state')</th>
                            <th scope="col" data-column-id="category">@lang('devices.category')</th>
                            <th scope="col" data-column-id="brand">@lang('devices.brand')</th>
                            <th scope="col" data-column-id="model">@lang('devices.model')</th>
                            <th scope="col" data-column-id="comment">@lang('devices.comment')</th>
                            <th scope="col" data-column-id="event">@lang('devices.eventgroup')</th>
                            <th scope="col" data-column-id="eventDate" data-header-css-class="mid-cell">@lang('devices.eventdate')</th>
                            <th scope="col" data-column-id="location">@lang('devices.location')</th> <!-- <th scope="col" data-column-id="groupName">Event (Group)</th> -->

                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($list))
                            @foreach($list as $device)
                              <tr>
                                @include('partials/device-comment-photo', ['comment' => $device->problem ])
                                <td><a href="/device/page-edit/<?php echo $device->id; ?>"><?php echo $device->id; ?></a></td>
                                @include('partials/device-status', ['status' => $device->repair_status])
                                <td><?php echo $device->category_name; ?></td>
                                <td><?php echo $device->brand; ?></td>
                                <td><?php echo $device->model; ?></td>
                                <td><?php echo $device->problem; ?></td>
                                <td><?php echo $device->group_name; ?></td>
                                <td><?php echo strftime('%Y-%m-%d', $device->event_date); ?></td>
                                <td><?php echo $device->event_location; ?></td>
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
