@php( $user_preferences = session('column_preferences') )
<tr>
    <td colspan="3" class="deviceID">
        <button type="button" class="btn btn-device-toggle btn-secondary collapsed"
            data-toggle="collapse" aria-controls="row-{{{ $device->iddevices }}}" aria-expanded="false"
            data-target="#row-{{{ $device->iddevices }}}">
            <span class="btn-state-1">@lang('devices.view_record')</span>
            <span class="btn-state-2">@lang('devices.close_record')</span>
        </button>
    </td>
    <td class="category" @if( !App\Helpers\Fixometer::checkColumn('category', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->deviceCategory->name }}}
    </td>
    @if ($powered)
    <td class="brand" @if( !App\Helpers\Fixometer::checkColumn('brand', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->brand }}}
    </td>
    <td class="model" @if( !App\Helpers\Fixometer::checkColumn('model', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->model }}}
    </td>
    @else
    <td class="item_type" @if( !App\Helpers\Fixometer::checkColumn('item_type', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->item_type }}}
    </td>
    @endif
    <td class="problem" @if( !App\Helpers\Fixometer::checkColumn('problem', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->getShortProblem() }}}
    </td>
    <td class="group_name" @if( !App\Helpers\Fixometer::checkColumn('group_name', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->deviceEvent->theGroup->name }}}
    </td>
    <td class="event_date" @if( !App\Helpers\Fixometer::checkColumn('event_date', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->deviceEvent->getEventDate() }}}
    </td>
    @include('partials/device-status', ['status' => $device->repair_status])
</tr>

<tr id="row-{{{ $device->iddevices }}}" class="collapse">
    @if( !is_null($user_preferences) )
        <td colspan="{{{ count($user_preferences) + 3 }}}" class="device-colspan p-0">
    @else
        <td colspan="10" class="device-colspan p-0">
    @endif
        <div class="table-device-details">
            <div class="form-group">
                <div class="dummy-label">@lang('devices.devices_description'):</div>
                <div class="dummy-field">
                    {{{ $device->getProblem() }}}
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3">
                    <div class="form-group">
                        <div class="dummy-label">@lang('devices.repair_status'):</div>
                        <div class="dummy-field">{{{ $device->getRepairStatus() }}}</div>
                    </div>
                </div>
                @if( !is_null($device->getNextSteps()) )
                    <div class="col-lg-3">
                        <div class="form-group">
                            <div class="dummy-label">@lang('devices.repair_details'):</div>
                            <div class="dummy-field">{{{ $device->getNextSteps() }}}</div>
                        </div>
                    </div>
                @endif
                @if( !is_null($device->getSpareParts()) )
                    <div class="col-lg-4">
                        <div class="form-group">
                            <div class="dummy-label">@lang('devices.spare_parts_required'):</div>
                            <div class="dummy-field">{{{ $device->getSpareParts() }}}</div>
                        </div>
                    </div>
                @endif
                @if( $device->barriers->count() > 0 )
                    <div class="col-lg-4">
                        <div class="form-group">
                            <div class="dummy-label">@lang('devices.repair_barrier'):</div>
                            <div class="dummy-field">
                                @foreach( $device->barriers as $barrier )
                                    {{{ $barrier->barrier }}}<br>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-lg-2">
                    <div class="form-group">
                        <div class="dummy-label">@lang('devices.age'):</div>
                        <div class="dummy-field">{{{ $device->getAge() }}}</div>
                    </div>
                </div>

            </div>
            <div class="row">

                @if( $device->urls->count() > 0 )
                    <div class="col-lg-5">
                      <div class="form-group mb-3">
                          <div class="dummy-label">@lang('devices.useful_repair_urls'):</div>
                      </div>
                    </div>
                    <div class="col-lg-5">
                      <div class="form-group mb-3">
                          <div class="dummy-label">@lang('devices.useful_repair_info'):</div>
                      </div>
                    </div>
                    @foreach( $device->urls as $url )
                      <div class="col-lg-5">
                          <div class="form-group mb-3">
                              <div class="dummy-field">{{{ $url->url }}}</div>
                          </div>
                      </div>
                      <div class="col-lg-5">
                          <div class="form-group mb-3">
                              <div class="dummy-field">
                                  @if ($url->source == 1) @lang('devices.from_manufacturer') @endif
                                  @if ($url->source == 2) @lang('devices.from_third_party') @endif
                              </div>
                          </div>
                      </div>
                    @endforeach
                @endif
            </div>

            @if( count($device->getImages()) > 0 )
                <div class="dummy-label mt-3">@lang('devices.uploaded_photos'):</div>
                    <ul class="photo-list photo-list__devices">
                        @foreach( $device->getImages() as $image )
                            <li>
                                <a href="/uploads/{{ $image->path }}" data-toggle="lightbox">
                                    <img src="/uploads/thumbnail_{{ $image->path }}" alt="placeholder" width="120" class="img-fluid">
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </td>
</tr>
