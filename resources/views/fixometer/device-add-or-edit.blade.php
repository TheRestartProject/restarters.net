<form id="device-add-or-edit" class="add-device" data-device="{{ $device->iddevices }}" method="post" enctype="multipart/form-data">
    <div class="collapse" id="add-device-{{ $powered ? 'powered' : 'unpowered' }}">
        <div class="device-info">
            <div class="card-event-add-item card flex-grow-1 border border-top-0 border-bottom-1 border-left-0 border-right border-white">
                <div class="card-body d-flex flex-column">
                    <h3>ITEM</h3>
                    <div class="mt-4">
                        <div class="mb-2 device-select-row">
                            <div class="form-control form-control__select form-control-lg d-inline">
                                <select name="category" id="category-{{ $device->iddevices }}" class="category select2">
                                    <option value="">@lang('devices.category')</option>
                                    @foreach( $clusters as $cluster )
                                        @php
                                        $empty = true;

                                        foreach( $cluster->categories as $category ) {
                                            if ($powered && $category->powered || !$powered && !$category->powered) {
                                                $empty = false;

                                            }
                                        }
                                        @endphp
                                        @if (!$empty)
                                            <optgroup label="{{{ $cluster->name }}}">
                                                @foreach( $cluster->categories as $category )
                                                    @if ($powered && $category->powered || !$powered && !$category->powered)
                                                        @if( $device->category == $category->idcategories)
                                                            <option value="{{{ $category->idcategories }}}" selected>{{{ $category->name }}}</option>
                                                        @else
                                                            <option value="{{{ $category->idcategories }}}">{{{ $category->name }}}</option>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    @endforeach
                                    @if( $device->category == 46 )
                                        <option value="46" selected>@lang('partials.category_none')</option>
                                    @else
                                        <option value="46">@lang('partials.category_none')</option>
                                    @endif
                                </select>
                            </div>
                            <div data-toggle="popover" data-placement="left" data-html="true" data-content="@lang('devices.tooltip_category')" class="ml-3 mt-2">
                                <img src="/icons/info_ico_black.svg">
                            </div>
                        </div>

                        <div class="mb-2 device-select-row">
                            <div class="form-control form-control__select">
                                <select name="brand" class="select2-with-input" id="brand-{{ $device->iddevices }}">
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
                            <div></div>
                        </div>

                        <div class="mb-2 device-select-row">
                            <div class="form-group">
                                <input type="text" class="form-control field" id="model-{{ $device->iddevices }}" name="model" value="{{ $device->model }}" placeholder="@lang('partials.model')" autocomplete="off">
                            </div>
                            <div data-toggle="popover" data-placement="left" data-html="true" data-content="@lang('devices.tooltip_model')" class="ml-3 mt-2">
                                <img src="/icons/info_ico_black.svg">
                            </div>
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
                                    <input type="number" class="form-control field" id="age-{{ $device->iddevices }}" name="age" min="0" step="0.5" value="{{ $device->age }}" autocomplete="off">
                                </div>
                            </div>
                            <span class="text-black text-right mb-1">
                            @lang('devices.age_approx')
                        </span>
                        </div>
{{--                        <p class="text-danger">TODO Add photos before add device, but only if quantity is 1.</p>--}}
                    </div>
                </div>
            </div>

            <div class="card card-event-add-item flex-grow-1 border border-top-0 border-bottom-1 border-left-0 border-right border-white">
                <div class="card-body">
                    <h3>REPAIR</h3>
                    <div class="mt-4 d-flex flex-column">
                        <div class="form-control form-control__select mb-2 col-device">
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
                                @elseif ( $device->repair_status == 3 )
                                    <option value="1">@lang('partials.fixed')</option>
                                    <option value="2">@lang('partials.repairable')</option>
                                    <option value="3" selected>@lang('partials.end_of_life')</option>
                                @else
                                    <option value="1">@lang('partials.fixed')</option>
                                    <option value="2">@lang('partials.repairable')</option>
                                    <option value="3">@lang('partials.end_of_life')</option>
                                @endif
                            </select>
                        </div>

                        <div class="form-control form-control__select mb-2 col-device">
                            <select class="repair_details select2 repair-details-edit" name="repair_info" id="repair-info-{{ $device->iddevices }}">
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

                        <div class="form-control form-control__select form-control__select_placeholder mb-2 col-device">
                            <select class="select2 spare-parts" name="spare_parts" id="spare-parts-{{ $device->iddevices }}">
                                <option @if ( $device->spare_parts == 1 && is_null($device->parts_provider) ) value="4" @else value="0" @endif>@lang('general.please_select')</option>
                                <option value="1" @if ( $device->spare_parts == 1 && !is_null($device->parts_provider) ) selected @endif>@lang('partials.yes_manufacturer')</option>
                                <option value="3" @if ( $device->parts_provider == 2 ) selected @endif>@lang('partials.yes_third_party')</option>
                                <option value="2" @if ( $device->spare_parts == 2 ) selected @endif>@lang('partials.no')</option>
                            </select>
                        </div>

                        <div class="form-control form-control__select form-control__select_placeholder mb-2 col-device">
                            <select class="select2 select2-repair-barrier repair-barrier" name="barrier" multiple id="barrier-{{ $device->iddevices }}">
                                <option></option>
                                @foreach( FixometerHelper::allBarriers() as $barrier )
                                    <option value="{{{ $barrier->id }}}" @if ( $device->barriers->contains($barrier->id) ) selected @endif>{{{ $barrier->barrier }}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-event-add-item flex-grow-1 border border-top-0 border-bottom-1 border-left-0 border-right-0 border-white">
                <div class="card-body">
                    <h3>ASSESSMENT</h3>
                    <div class="mt-4">
                        <div class="mb-2 device-select-row">
                            <div class="form-group">
                                <textarea class="form-control" rows="6" name="problem" id="problem-{{ $device->iddevices }}" placeholder="@lang('partials.description_of_problem_solution')">{!! $device->problem !!}</textarea>
                            </div>
                            <div data-toggle="popover" data-placement="left" data-html="true" data-content="@lang('devices.tooltip_problem')"  class="ml-3 mt-2">
                                <img src="/icons/info_ico_black.svg">
                            </div>
                        </div>
                        @include('partials.useful-repair-urls-add-or-edit', ['urls' => $device->urls, 'device' => $device])

                        <div class="form-check d-flex align-items-center justify-content-start">
                            <input class="form-check-input form-check-large" type="checkbox" name="wiki" id="wiki-{{ $device->iddevices }}" value="1" @if( $device->wiki == 1 ) checked @endif>
                            <label class="form-check-label" for="wiki-{{ $device->iddevices }}">@lang('partials.solution_text2')</label>
                        </div>

{{--                        <p class="text-danger">TODO Notes field does not exist yet</p>--}}
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center flex-wrap card-event-add-item mb-4 pt-4 pb-4">
            <button type="submit" class="btn btn-primary btn-save2">@lang('partials.add_device')</button>
            <div class="form-control form-control__select flex-md-shrink-1 ml-4 mr-4" style="width: 70px;">
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
            <a class="collapsed" data-toggle="collapse" href="#add-device" role="button" aria-expanded="false" aria-controls="add-device">
                <button class="btn btn-tertiary" type="button">@lang('partials.cancel')</button>
            </a>
        </div>
    </div>
</form>
