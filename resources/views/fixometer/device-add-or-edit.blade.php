<form id="data-add" class="add-device" data-device="{{ $device->iddevices }}" method="post" enctype="multipart/form-data">
    <div class="device-info collapse" id="add-device">
        <div class="card-event-add-item card flex-grow-1 border border-top-0 border-bottom-0 border-left-0 border-right border-white">
            <div class="card-body d-flex flex-column">
                <h3>ITEM</h3>
                <div class="mt-4">
                    <div class="form-control form-control__select form-control-lg mb-2">
                        <select name="category-{{ $device->iddevices }}" id="category-{{ $device->iddevices }}" class="category select2">
                            <option value="">@lang('devices.category')</option>
                            @foreach( $clusters as $cluster )
                                <optgroup label="{{{ $cluster->name }}}">
                                    @foreach( $cluster->categories as $category )
                                        @if( $device->category == $category->idcategories )
                                            <option value="{{{ $category->idcategories }}}" selected>{{{ $category->name }}}</option>
                                        @else
                                            <option value="{{{ $category->idcategories }}}">{{{ $category->name }}}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            @endforeach
                            @if( $device->category == 46 )
                                <option value="46" selected>@lang('partials.category_none')</option>
                            @else
                                <option value="46">@lang('partials.category_none')</option>
                            @endif
                        </select>
                    </div>

                    <div class="form-control form-control__select mb-2">
                        <select name="brand-{{ $device->iddevices }}" class="select2-with-input" id="brand-{{ $device->iddevices }}">
                            @php($i = 1)
                            @if( empty($device->brand) )
                                <option value="" selected>Brand</option>
                            @else
                                <option value="">Brand</option>
                            @endif
                            @foreach($brands as $brand)
                                @if ($device->brand == $brand->brand_name)
                                    <option value="{{ $brand->brand_name }}" selected>{{ $brand->brand_name }}</option>
                                    @php($i++)
                                @else
                                    <option value="{{ $brand->brand_name }}">{{ $brand->brand_name }}</option>
                                @endif
                            @endforeach
                            @if( $i == 1 && !empty($device->brand) )
                                <option value="{{ $device->brand }}" selected>{{ $device->brand }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="form-group mb-2">
                        <input type="text" class="form-control field" id="model-{{ $device->iddevices }}" name="model-{{ $device->iddevices }}" value="{{ $device->model }}" placeholder="@lang('partials.model')" autocomplete="off">
                    </div>

                    <div class="device-field-row align-items-center mb-2">
                        <label class="text-white text-bold">
                            @lang('devices.weight')*
                        </label>
                        <div class="display-weight">
                            <div class="input-group">
                                <input disabled type="number" class="weight form-control form-control-lg field numeric" id="weight-{{ $device->iddevices }}" name="weight" min="0.01" step=".01" autocomplete="off" value="{{ $device->estimate }}">
                            </div>
                        </div>
                        <span class="text-white text-right mb-1">
                            @lang('devices.required_impact')
                        </span>
                    </div>

                    <div class="device-field-row align-items-center mb-2">
                        <label class="text-black text-bold">
                            @lang('devices.age')
                        </label>
                        <div class="display-weight">
                            <div class="input-group">
                                <input type="number" class="form-control field" id="age-{{ $device->iddevices }}" name="age-{{ $device->iddevices }}" min="0" step="0.5" value="{{ $device->age }}" autocomplete="off">
                            </div>
                        </div>
                        <span class="text-black text-right mb-1">
                            @lang('devices.age_approx')
                        </span>
                    </div>

                    <p class="text-danger">TODO Info buttons, add photos before add device</p>
                </div>
            </div>
        </div>

        <div class="card card-event-add-item flex-grow-1 border border-top-0 border-bottom-0 border-left-0 border-right border-white">
            <div class="card-body">
                <h3>REPAIR</h3>
                <div class="mt-4">
                    <div class="form-control form-control__select mb-2">
                        <select class="select2 repair-status" name="repair_status" id="status-{{ $device->iddevices }}" data-device="{{ $device->iddevices }}" placeholder="Description of problem">
                            <option value="0">@lang('general.please_select')</option>
                            @if ( $device->repair_status == 1 )
                                <option value="1" selected>@lang('partials.fixed')</option>
                                <option value="2">@lang('partials.repairable')</option>
                                <option value="3">@lang('partials.end_of_life')</option>
                            @elseif ( $device->repair_status == 2 )
                                <option value="1">@lang('partials.fixed')</option>
                                <option value="2" selected>@lang('partials.repairable')</option>
                                <option value="3">@lang('partials.end_of_life')</option>
                            @else
                                <option value="1">@lang('partials.fixed')</option>
                                <option value="2">@lang('partials.repairable')</option>
                                <option value="3" selected>@lang('partials.end_of_life')</option>
                            @endif
                        </select>
                    </div>

                    <div class="form-control form-control__select mb-2">
                        <select class="repair_details select2 repair-details-edit" name="repair-info" id="repair-info-{{ $device->iddevices }}">
                            <option value="0">@lang('partials.repair_details') ?</option>
                            @if ( $device->more_time_needed == 1 )
                                <option value="1" selected>@lang('partials.more_time')</option>
                                <option value="2">@lang('partials.professional_help')</option>
                                <option value="3">@lang('partials.diy')</option>
                            @elseif ( $device->professional_help == 1 )
                                <option value="1">@lang('partials.more_time')</option>
                                <option value="2" selected>@lang('partials.professional_help')</option>
                                <option value="3">@lang('partials.diy')</option>
                            @elseif ( $device->do_it_yourself == 1 )
                                <option value="1" >@lang('partials.more_time')</option>
                                <option value="2">@lang('partials.professional_help')</option>
                                <option value="3" selected>@lang('partials.diy')</option>
                            @else
                                <option value="1">@lang('partials.more_time')</option>
                                <option value="2">@lang('partials.professional_help')</option>
                                <option value="3">@lang('partials.diy')</option>
                            @endif
                        </select>
                    </div>

                    <div class="form-control form-control__select mb-2">
                        <select class="select2 spare-parts" name="spare-parts-{{ $device->iddevices }}" id="spare-parts-{{ $device->iddevices }}">
                            <option @if ( $device->spare_parts == 1 && is_null($device->parts_provider) ) value="4" @else value="0" @endif>@lang('general.please_select')</option>
                            <option value="1" @if ( $device->spare_parts == 1 && !is_null($device->parts_provider) ) selected @endif>@lang('partials.yes_manufacturer')</option>
                            <option value="3" @if ( $device->parts_provider == 2 ) selected @endif>@lang('partials.yes_third_party')</option>
                            <option value="2" @if ( $device->spare_parts == 2 ) selected @endif>@lang('partials.no')</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-event-add-item flex-grow-1">
            <div class="card-body">
                <h3>ASSESSMENT</h3>
                <div class="mt-4">
                    <div class="form-group">
                        <textarea class="form-control" rows="6" name="problem-{{ $device->iddevices }}" id="problem-{{ $device->iddevices }}" placeholder="@lang('partials.description_of_problem_solution')">{!! $device->problem !!}</textarea>
                    </div>
                    @include('partials.useful-repair-urls', ['urls' => $device->urls, 'device' => $device])

                    <div class="form-check d-flex align-items-center justify-content-start">
                        <input class="form-check-input form-check-large" type="checkbox" name="wiki-{{ $device->iddevices }}" id="wiki-{{ $device->iddevices }}" value="1" @if( $device->wiki == 1 ) checked @endif>
                        <label class="form-check-label" for="wiki-{{ $device->iddevices }}">@lang('partials.solution_text2')</label>
                    </div>

                    <p class="text-danger">TODO Notes field - does that exist?</p>
                </div>
            </div>
        </div>

        <div class="row row-compressed-xs nested-fields d-lg-table col-lg-12">

            <div class="col-12 col-sm-6 col-device-auto">
            </div>


            <div class="col-5 col-device-auto">
            </div>

            <div class="col-12 col-sm-4 form-group col-device-auto">
                <label for="status-{{ $device->iddevices }}">@lang('partials.status'):</label>
            </div>

            <div class="col-12 col-sm-4 form-group col-device <?php echo ($device->repair_status == 2 ? 'col-device-auto' : 'd-none'); ?>">
                <label for="repair-info-{{ $device->iddevices }}">@lang('partials.repair_details'):</label>
            </div>

            <div class="col-12 col-sm-4 form-group col-device <?php echo ($device->repair_status == 1 || $device->repair_status == 2 ? 'col-device-auto' : 'd-none'); ?>">
                <label for="spare-parts-{{ $device->iddevices }}">@lang('devices.spare_parts_required'):</label>
            </div>

            <div class="col-12 col-sm-12 col-md-4 form-group col-device <?php echo ($device->repair_status == 3 ? 'col-device-auto' : 'd-none'); ?>">
                <label for="repair_barrier">@lang('devices.repair_barrier'):</label>
                <div class="form-control form-control__select form-control__select_placeholder">
                    <select name="barrier-{{ $device->iddevices }}[]" multiple id="barrier-{{ $device->iddevices }}" class="form-control field select2-repair-barrier repair-barrier">
                        <option></option>
                        @foreach( FixometerHelper::allBarriers() as $barrier )
                            <option value="{{{ $barrier->id }}}" @if ( $device->barriers->contains($barrier->id) ) selected @endif>{{{ $barrier->barrier }}}</option>
                        @endforeach
                    </select>
                </div>
            </div>


        </div><!-- /row -->
        <div class="row row-compressed-xs nested-fields table-row-more">
            <div class="col-12 col-lg-6 flex-column d-flex">

            </div>
            <div class="col-12 col-lg-6 flex-column d-flex">

                <div class="table-cell-upload">

                    <div class="row mt-4">
                        <div class="col-md-12 d-flex align-content-center flex-column">
                        </div>
                        <div class="col-md-12 d-flex justify-content-end mb-2">
                            <label for="add-quantity">@lang('partials.quantity'):</label>
                            <div class="form-control form-control__select flex-md-shrink-1" style="width: 70px;">
                                <select name="quantity" class="quantity select2" id="add-quantity" >
                                    <option selected value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-save2 flex-md-shrink-1">@lang('partials.save')</button>
                        </div>
                    </div>
                </div><!-- / table-cell-upload -->
            </div>
        </div><!-- /row -->
    </div>
</form>
