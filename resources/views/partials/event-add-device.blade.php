@if(FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) )

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
                                            <option value="">-- Category --</option>
                                            @foreach( $clusters as $cluster )
                                            <optgroup label="{{{ $cluster->name }}}">
                                                @foreach( $cluster->categories as $category )
                                                    <option value="{{{ $category->idcategories }}}">{{{ $category->name }}}</option>
                                                @endforeach
                                            </optgroup>
                                            @endforeach
                                            <option value="46">None of the above</option>
                                        </select>
                                    </div>
                                    <div id="display-weight" style="display: none;">
                                        <div class="input-group">
                                            <input type="number" class="form-control field weight" name="weight" min="0.01" step=".01" placeholder="Est. weight" autocomplete="off" disabled>
                                            <div class="input-group-append">
                                                <span class="input-group-text" id="validationTooltipUsernamePrepend">kg</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td width="150">
                                    <div class="form-control form-control__select">
                                        <select name="brand" class="brand select2-with-input">
                                            <option value="">-- Brand --</option>
                                            <option value=""></option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->brand_name }}">{{ $brand->brand_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input type="text" class="form-control field" class="model" name="model" placeholder="Model" autocomplete="off">
                                    </div>
                                </td>
                                <td width="100">
                                    <div class="form-group">
                                        <input type="number" class="form-control field" class="age" name="age" min="0" step="0.5" placeholder="Age (yrs)" autocomplete="off">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input type="text" class="form-control field" class="problem" name="problem" placeholder="Description of problem" autocomplete="off">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-control form-control__select">
                                        <select name="repair_status" class="repair_status select2">
                                            <option value="0">-- Status --</option>
                                            <option value="1">Fixed</option>
                                            <option value="2">Repairable</option>
                                            <option value="3">End of Life</option>
                                        </select>
                                    </div>
                                    <div id="repair-more" style="display: none;">
                                        <div class="form-control form-control__select">
                                            <select name="repair_details" class="repair_details select2" disabled>
                                                <option value="0">-- Repair details --</option>
                                                <option value="1">More time needed</option>
                                                <option value="2">Professional help</option>
                                                <option value="3">Do it yourself</option>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-control form-control__select">
                                        <select name="spare_parts" class="spare_parts select2">
                                            <option value="0">-- Spare parts --</option>
                                            <option value="1">Yes</option>
                                            <option value="2">No</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <input type="submit" class="btn btn-primary btn-add" value="Add">
                                </td>
                                <td>
                                    <div class="form-control form-control__select">
                                        <select name="quantity" class="quantity select2">
                                            <option value="1">1</option>
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
