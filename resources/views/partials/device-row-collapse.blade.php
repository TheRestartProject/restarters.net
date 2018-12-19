<tr>
    <td colspan="3" class="deviceID">
        <button type="button" class="btn btn-device-toggle btn-primary collapsed"
            data-toggle="collapse" aria-controls="row-1" aria-expanded="false"
            data-target="#row-1">
            <span class="btn-state-1">View</span>
            <span class="btn-state-2">Close</span>
        </button>
    </td>
    <td class="category">
        {{{ $device->category_name }}}
    </td>
    <td class="brand">
        {{{ $device->brand }}}
    </td>
    <td class="model">
        {{{ $device->model }}}
    </td>
    <td class="comment">
        {{{ $device->problem }}}
    </td>
    <td class="eventGroup">
        {{{ $device->group_name }}}
    </td>
    <td class="eventDate">
        {{{ strftime('%Y-%m-%d', $device->event_date) }}}
    </td>
    @include('partials/device-status', ['status' => $device->repair_status])

</tr>

<tr id="row-1" class="collapse">
    <td colspan="10" class="p-0">
        <div class="table-device-details">
            <div class="form-group">
                <div class="dummy-label">@lang('devices.devices_description'):</div>
                <div class="dummy-field">
                    {{{ $device->problem }}}
                </div>
            </div>
            <div class="row">

                <div class="col-lg-3">
                    <div class="form-group">
                        <div class="dummy-label">@lang('devices.repair_status'):</div>
                        <div class="dummy-field">Repairable</div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <div class="dummy-label">@lang('devices.repair_details'):</div>
                        <div class="dummy-field">More time needed</div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <div class="dummy-label">@lang('devices.spare_parts_required'):</div>
                        <div class="dummy-field">Yes - manufacturer</div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <div class="dummy-label">@lang('devices.age'):</div>
                        <div class="dummy-field">Third party</div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="form-group">
                        <div class="dummy-label">@lang('devices.label_url'):</div>
                        <div class="dummy-field">http://fixo.meter:8888</div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="form-group">
                        <div class="dummy-label">@lang('devices.label_info'):</div>
                        <div class="dummy-field">Donec ut purus ac nisl</div>
                    </div>
                </div>
            </div>
            <div class="dummy-label">@lang('devices.uploaded_photos'):</div>
                <ul class="photo-list photo-list__devices">
                    <li>
                        <a href="" data-toggle="lightbox">
                        <img src="" alt="placeholder" width="120" class="img-fluid">
                        </a>
                    </li>
                    <li>
                        <a href="" data-toggle="lightbox">
                        <img src="" alt="placeholder" width="120" class="img-fluid">
                        </a>
                    </li>
                    <li>
                        <a href="" data-toggle="lightbox">
                        <img src="" alt="placeholder" width="120" class="img-fluid">
                        </a>
                    </li>
              </ul>
        </div>
    </td>
</tr>
