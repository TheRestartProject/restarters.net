@extends('layouts.app')

@section('title')
Repairs
@endsection

@section('content')
<form id="device-search" action="/device/search/" method="get">

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
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">

                <div class="col-lg-3">


                    <div class="collapse d-lg-block d-xl-block fixed-overlay-md" id="collapseFilter">

                            <div class="form-row">
                                <div class="form-group col mobile-search-bar-md my-0">

                            <button class="btn btn-secondary btn-groups my-1" type="submit" disabled>
                                @lang('devices.number_of_repairs'): {{ $list->total() }}
                            </button>

                                    <button type="button" class="d-lg-none mobile-search-bar-md__close" data-toggle="collapse"
                                        data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter"><svg
                                            width="21" height="21" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                            xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/"
                                            style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;">
                                            <title>Close</title>
                                            <g>
                                                <path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z" />
                                                <path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z" />
                                            </g>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" name="sort_direction" value="{{{ $sort_direction }}}">

                            @php( $active_filter = false )

                            @foreach( FixometerHelper::filterColumns() as $column => $label )
                                <input @if( $sort_column == $column ) checked @endif type="radio" name="sort_column" value="{{{ $column }}}" id="label-{{{ $column }}}" class="sr-only">
                            @endforeach

                            <aside id="side-collapse" class="edit-panel edit-panel__side">

                                <fieldset class="side-collapse">

                                    @if( empty($_GET) || !empty($selected_categories) || !empty($brand) || !empty($model) )

                                        @php( $active_filter = true )

                                        <legend id="heading-1">
                                            <button type="button" class="btn btn-link" data-toggle="collapse"
                                                data-target="#collapse-side-1" aria-expanded="true" aria-controls="collapse-side-1">
                                                @lang('devices.device_info')
                                            </button>
                                        </legend>

                                        <div id="collapse-side-1" class="collapse show" aria-labelledby="heading-1">
                                    @else
                                        <legend id="heading-1">
                                            <button type="button" class="btn btn-link collapsed" data-toggle="collapse"
                                                data-target="#collapse-side-1" aria-expanded="true" aria-controls="collapse-side-1">
                                                @lang('devices.device_info')
                                            </button>
                                        </legend>

                                        <div id="collapse-side-1" class="collapse" aria-labelledby="heading-1">
                                    @endif

                                        <div class="form-group">
                                            <label for="items_cat">@lang('devices.category'):</label>
                                            <div class="form-control form-control__select">
                                                <select id="categories" name="categories[]" class="form-control select2-tags"
                                                    multiple title="Choose categories...">
                                                    @if(isset($categories))
                                                    @foreach($categories as $cluster)
                                                    <optgroup label="<?php echo $cluster->name; ?>">
                                                        @foreach($cluster->categories as $c)
                                                        @if (!empty($selected_categories) && in_array($c->idcategories, $selected_categories))
                                                        <option value="<?php echo $c->idcategories; ?>" selected>
                                                            <?php echo $c->name; ?>
                                                        </option>
                                                        @else
                                                        <option value="<?php echo $c->idcategories; ?>">
                                                            <?php echo $c->name; ?>
                                                        </option>
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
                                            <label for="brand">@lang('devices.device_brand'):</label>
                                            <input type="text" class="form-control field" id="brand" name="brand"
                                                placeholder="e.g. Apple..." value="{{ $brand }}">
                                        </div>

                                        <div class="form-group">
                                            <label for="model">@lang('devices.device_model'):</label>
                                            <input type="text" class="form-control field" id="model" name="model"
                                                placeholder="e.g. iPhone..." value="{{ $model }}">
                                        </div>

                                    </div><!-- collapse-side-1-->
                                </fieldset>

                                <fieldset class="side-collapse">

                                    @if( !empty($status) || !empty($problem) || !empty($wiki) )

                                        @php( $active_filter = true )

                                        <legend id="heading-2">
                                            <button type="button" class="btn btn-link" data-toggle="collapse"
                                                data-target="#collapse-side-2" aria-expanded="true" aria-controls="collapse-side-2">
                                                @lang('devices.repair_info')
                                            </button>
                                        </legend>

                                        <div id="collapse-side-2" class="collapse show" aria-labelledby="heading-2">
                                    @else
                                        <legend id="heading-2">
                                            <button type="button" class="btn btn-link collapsed" data-toggle="collapse"
                                                data-target="#collapse-side-2" aria-expanded="true" aria-controls="collapse-side-2">
                                                @lang('devices.repair_info')
                                            </button>
                                        </legend>

                                        <div id="collapse-side-2" class="collapse" aria-labelledby="heading-2">
                                    @endif

                                        <div class="form-group">
                                            <label for="status">Repair Status:</label>
                                            <div class="form-control form-control__select">
                                                <select id="status" name="status[]" class="form-control select2-tags"
                                                    multiple title="Device status...">
                                                    <option @if (!empty($status) && in_array(1, $status)) selected @endif value="1">Fixed</option>
                                                    <option @if (!empty($status) && in_array(2, $status)) selected @endif value="2">Repairable</option>
                                                    <option @if (!empty($status) && in_array(3, $status)) selected @endif value="3">End</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="problem">@lang('devices.search_comments'):</label>
                                            <input type="text" class="form-control field" id="problem" name="problem"
                                                placeholder="e.g. screen..." value="{{ $problem }}">
                                        </div>

                                        <div class="form-group">
                                            <label for="suitable-for-wiki">@lang('devices.suitable'):</label>
                                            <input type="checkbox" id="suitable-for-wiki" name="wiki" value="1" {{ $wiki ? 'checked' : '' }} />
                                            <small class="form-text text-muted">@lang('devices.suitable_help')</small>
                                        </div>
                                    </div><!-- collapse-side-2-->

                                </fieldset>

                                <fieldset class="side-collapse">

                                    @if( !empty($selected_groups) || !empty($from_date) || !empty($to_date) )

                                        @php( $active_filter = true )

                                        <legend id="heading-3">
                                            <button type="button" class="btn btn-link" data-toggle="collapse"
                                                data-target="#collapse-side-3" aria-expanded="true" aria-controls="collapse-side-3">
                                                @lang('devices.event_info')
                                            </button>
                                        </legend>

                                        <div id="collapse-side-3" class="collapse show" aria-labelledby="heading-3">
                                    @else
                                        <legend id="heading-3">
                                            <button type="button" class="btn btn-link collapsed" data-toggle="collapse"
                                                data-target="#collapse-side-3" aria-expanded="true" aria-controls="collapse-side-3">
                                                @lang('devices.event_info')
                                            </button>
                                        </legend>

                                        <div id="collapse-side-3" class="collapse" aria-labelledby="heading-3">
                                    @endif

                                        <div class="form-group">
                                            <label for="items_group">@lang('devices.group'):</label>
                                            <div class="form-control form-control__select">
                                                <select id="groups" name="groups[]" class="form-control select2-tags"
                                                    multiple data-live-search="true" title="Choose groups...">
                                                    @if(isset($groups))
                                                    @foreach($groups as $g)
                                                    @if (!empty($selected_groups) && in_array($g->idgroups,
                                                    $selected_groups))
                                                    <option value="<?php echo $g->idgroups; ?>" selected>
                                                        <?php echo $g->name; ?>
                                                    </option>
                                                    @else
                                                    <option value="<?php echo $g->idgroups; ?>">
                                                        <?php echo $g->name; ?>
                                                    </option>
                                                    @endif
                                                    @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="from-date">@lang('devices.from_date'):</label>
                                            <input type="date" class="field form-control" id="search-from-date" name="from-date"
                                                value="{{ $from_date }}">
                                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        </div>

                                        <div class="form-group">
                                            <label for="to-date">@lang('devices.to_date'):</label>
                                            <input type="date" class="field form-control" id="search-to-date" name="to-date"
                                                value="{{ $to_date }}">
                                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        </div>

                                    </div><!-- collapse-side-3-->

                                </fieldset>

                            </aside>

                            <button class="btn btn-primary btn-groups" type="submit">@lang('devices.search_all_devices')</button>

                    </div><!-- /collapseFilter -->
                </div>

                <div class="col-lg-9">

                    <div class="row">
                        <div class="col-12">

                            <div class="d-flex flex-row align-content-center justify-content-end">


                                <div class="btn-group btn-group__duo" role="group" aria-label="Filter options">

                                    <button class="reveal-filters btn btn-secondary d-lg-none d-xl-none" type="button"
                                        data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false"
                                        aria-controls="collapseFilter">Show filters</button>

                                    <div class="dropdown">
                                        <button class="btn btn-primary dropdown-toggle" id="dropdownMenu2" data-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false"><span class="sr-only">Items</span><svg
                                                width="14" height="12" viewBox="0 0 12 10" xmlns="http://www.w3.org/2000/svg"
                                                fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round"
                                                stroke-miterlimit="1.414">
                                                <path d="M3.163.324A.324.324 0 0 0 2.84 0H.324A.324.324 0 0 0 0 .324v1.909c0 .179.145.324.324.324H2.84a.324.324 0 0 0 .323-.324V.324zm0 3.715a.324.324 0 0 0-.323-.324H.324A.324.324 0 0 0 0 4.039v1.91c0 .178.145.323.324.323H2.84a.323.323 0 0 0 .323-.323v-1.91zm0 3.715a.323.323 0 0 0-.323-.323H.324A.324.324 0 0 0 0 7.754v1.91c0 .179.145.324.324.324H2.84a.324.324 0 0 0 .323-.324v-1.91zM11.25.324A.324.324 0 0 0 10.926 0h-6.37a.323.323 0 0 0-.323.324v1.909c0 .179.144.324.323.324h6.37a.324.324 0 0 0 .324-.324V.324zm0 3.715a.324.324 0 0 0-.324-.324h-6.37a.323.323 0 0 0-.323.324v1.91c0 .178.144.323.323.323h6.37a.324.324 0 0 0 .324-.323v-1.91zm0 3.715a.324.324 0 0 0-.324-.323h-6.37a.323.323 0 0 0-.323.323v1.91c0 .179.144.324.323.324h6.37a.324.324 0 0 0 .324-.324v-1.91z"
                                                    fill="#fff" /></svg>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu2">
                                            @php( $user_preferences = session('column_preferences') )
                                            @foreach( FixometerHelper::filterColumns() as $column => $label )
                                                <label class="dropdown-item">
                                                    <input class="filter-columns" name="filter-columns[]" data-id="{{{ $column }}}" type="checkbox" value="{{{ $column }}}"
                                                        class="dropdown-item-checkbox" @if( FixometerHelper::checkColumn($column, $user_preferences) || is_null($user_preferences) ) checked @endif> {{{ $label }}}</input>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                </div>

                                @if (FixometerHelper::hasRole(Auth::user(), 'Administrator'))
                                    <a href="/export/devices/?{{{ Request::getQueryString() }}}" class="btn btn-primary btn-save ml-2">
                                        <i class="fa fa-download"></i>
                                        @lang('devices.export_device_data')
                                    </a>
                                @endif

                            </div>

                            <br>

                            <div class="table-responsive" id="sort-table">
                                <table class="table table-hover bootg table-devices" id="devices-table">
                                    <thead>
                                        <tr>

                                            @if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
                                                <th width="120" colspan="3"></th>
                                            @else
                                                <th width="120"></th>
                                            @endif

                                            <th scope="col" class="category" @if( !FixometerHelper::checkColumn('category', $user_preferences) ) style="display: none;" @endif>
                                                <label for="label-category" class="sort-column @if( $sort_column == 'category' ) sort-column-{{{ strtolower($sort_direction) }}} @endif">
                                                    @lang('devices.category')
                                                </label>
                                            </th>
                                            <th scope="col" class="brand" @if( !FixometerHelper::checkColumn('brand', $user_preferences) ) style="display: none;" @endif>
                                                <label for="label-brand" class="sort-column @if( $sort_column == 'brand' ) sort-column-{{{ strtolower($sort_direction) }}} @endif">
                                                    @lang('devices.brand')
                                                </label>
                                            </th>
                                            <th scope="col" class="model" @if( !FixometerHelper::checkColumn('model', $user_preferences) ) style="display: none;" @endif>
                                                <label for="label-model" class="sort-column @if( $sort_column == 'model' ) sort-column-{{{ strtolower($sort_direction) }}} @endif">
                                                    @lang('devices.model')
                                                </label>
                                            </th>
                                            <th scope="col" class="problem" @if( !FixometerHelper::checkColumn('problem', $user_preferences) ) style="display: none;" @endif>
                                                <label for="label-problem" class="sort-column @if( $sort_column == 'problem' ) sort-column-{{{ strtolower($sort_direction) }}} @endif">
                                                    @lang('devices.comment')
                                                </label>
                                            </th>
                                            <th scope="col" class="group_name" @if( !FixometerHelper::checkColumn('group_name', $user_preferences) ) style="display: none;" @endif>
                                                <label for="label-group_name" class="sort-column @if( $sort_column == 'group_name' ) sort-column-{{{ strtolower($sort_direction) }}} @endif">
                                                    @lang('devices.group')
                                                </label>
                                            </th>
                                            <th scope="col" class="event_date" @if( !FixometerHelper::checkColumn('event_date', $user_preferences) ) style="display: none;" @endif>
                                                <label for="label-event_date" class="sort-column @if( $sort_column == 'event_date' ) sort-column-{{{ strtolower($sort_direction) }}} @endif">
                                                    @lang('devices.devices_date')
                                                </label>
                                            </th>
                                            <th scope="col" class="repair_status" @if( !FixometerHelper::checkColumn('repair_status', $user_preferences) ) style="display: none;" @endif>
                                                <label for="label-repair_status" class="sort-column @if( $sort_column == 'repair_status' ) sort-column-{{{ strtolower($sort_direction) }}} @endif">
                                                    @lang('devices.state')
                                                </label>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php( $user = Auth::user() )
                                        @php( $is_admin = FixometerHelper::hasRole($user, 'Administrator') )
                                        @foreach($list as $device)
                                            @if ( $is_admin || $device->repaired_by == $user->id )
                                                @include('partials.device-row-with-edit')
                                            @else
                                                @include('partials.device-row-collapse')
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <br>

                            <div class="d-flex justify-content-center">
                                <nav aria-label="Page navigation example">
                                    <ul class="pagination">
                                        @if (!empty($_GET))
                                            {!! $list->appends(request()->input())->links() !!}
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

</form>

@endsection
