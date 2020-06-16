@if( Auth::check() && ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) ) )

    <form class="add-device" method="post" onkeypress="return event.keyCode != 13;">

      <input type="hidden" name="event_id" value="{{{ $formdata->id }}}">

        <div class="row">
            <div class="col-12" style="overflow:hidden">
                <div class="table-responsive">

                    <table class="table table-add" role="table">
                        <tbody>
                            <tr>
                                <td width="200">
                                    <div class="form-control form-control__select">
                                        <select id="device-start" name="category" class="category select2">
                                            <option value="">-- @lang('partials.category') --</option>
                                            @foreach( $clusters as $cluster )
                                            <optgroup label="{{{ $cluster->name }}}">
                                                @foreach( $cluster->categories as $category )
                                                    <option value="{{{ $category->idcategories }}}">{{{ $category->name }}}</option>
                                                @endforeach
                                            </optgroup>
                                            @endforeach
                                            <option value="46">@lang('partials.category_none')</option>
                                        </select>
                                    </div>
                                    <div class="display-weight d-none pt-1">
                                        <div class="input-group">
                                          <input type="number" class="form-control field weight" name="weight" min="0.01" step=".01" placeholder="Est. weight" autocomplete="off" disabled>
                                          <div class="input-group-append">
                                            <span class="input-group-text">kg</span>
                                          </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-sm-table-cell" width="150">
                                    <div class="form-control form-control__select">
                                        <select name="brand" class="brand select2-with-input">
                                            <option value="">-- @lang('partials.brand') --</option>
                                            <option value=""></option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->brand_name }}">{{ $brand->brand_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td class="d-none d-sm-table-cell">
                                    <div class="form-group">
                                        <input type="text" class="form-control field" class="model" name="model" placeholder="Model" autocomplete="off">
                                    </div>
                                </td>
                                <td class="d-none d-sm-table-cell" width="100">
                                    <div class="form-group">
                                        <input type="number" class="form-control field" class="age" name="age" min="0" step="0.5" placeholder="Age (yrs)" autocomplete="off">
                                    </div>
                                </td>
                                <td class="d-none d-sm-table-cell">
                                    <div class="form-group">
                                        <input type="text" class="form-control field" class="problem" name="problem" placeholder="Description of problem" autocomplete="off">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-control form-control__select">
                                        <select name="repair_status" class="repair_status select2">
                                            <option value="0">-- @lang('partials.status') --</option>
                                            <option value="1">@lang('partials.fixed')</option>
                                            <option value="2">@lang('partials.repairable')</option>
                                            <option value="3">@lang('partials.end_of_life')</option>
                                        </select>
                                    </div>
                                    <div class="d-none col-device">
                                        <div class="form-control form-control__select">
                                            <select name="repair_details" id="repair_details_edit" class="form-control field select2 repair-details-edit">
                                              <option value="0">-- Next steps --</option>
                                              <option value="1">@lang('partials.more_time')</option>
                                              <option value="2">@lang('partials.professional_help')</option>
                                              <option value="3">@lang('partials.diy')</option>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none col-device">
                                    <div class="form-control form-control__select">
                                        <select name="spare_parts" class="spare_parts spare-parts select2">
                                          <option value="0">-- Spare parts --</option>
                                          <option value="1">@lang('partials.yes_manufacturer')</option>
                                          <option value="3">@lang('partials.yes_third_party')</option>
                                          <option value="2">@lang('partials.no')</option>
                                        </select>
                                    </div>
                                </td>
                                <td class="d-none col-device">
                                    <div class="form-control form-control__select form-control__select_placeholder">
                                        <select name="barrier[]" multiple placeholder="-- Choose barriers to repair --" id="repair_barrier" class="form-control field select2-repair-barrier repair-barrier">
                                          @foreach( FixometerHelper::allBarriers() as $barrier )
                                            <option value="{{{ $barrier->id }}}">{{{ $barrier->barrier }}}</option>
                                          @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td class="d-table-cell d-sm-table-cell">
                                    <input type="submit" class="btn btn-secondary btn-add" value="Add">
                                </td>
                                <td class="d-none">
                                    <div class="form-control form-control__select">
                                        <select name="quantity" class="quantity select2">
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
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </form>


@endif
