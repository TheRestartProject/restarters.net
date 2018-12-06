<div class="form-group useful-repair-urls">
  <div class="d-flex">
    <label>@lang('devices.useful_repair_urls'):</label>
    <label>@lang('devices.useful_repair_info'):</label>
  </div>
    @foreach( $urls as $url )
    <div class="input-group save-url" data-device_id="{{{ $device->iddevices }}}" data-id="{{{ $url->id }}}">
      <input value="{{{ $url->url }}}" type="url" class="form-control mr-1" placeholder="@lang('devices.useful_repair_urls_helper')" aria-label="@lang('devices.useful_repair_urls_explanation')">
      <div class="form-control form-control__select ml-1">
        <select class="select2" name="source">
          <option value="">@lang('general.please_select')</option>
          <option value="1" @if ($url->source == 1) selected @endif>@lang('devices.from_manufacturer')</option>
          <option value="2" @if ($url->source == 2) selected @endif>@lang('devices.from_third_party')</option>
        </select>
      </div>
      @if(FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($device->event, Auth::user()->id) )
      <div class="input-group-append">
        <button class="btn btn-link" type="button"><span>-</span></button>
      </div>
      @endif
    </div>
    @endforeach
    @if(FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($device->event, Auth::user()->id) )
      <div class="input-group add-url" data-device_id="{{{ $device->iddevices }}}">
        <input type="url" class="form-control mr-1" placeholder="@lang('devices.useful_repair_urls_helper')" aria-label="@lang('devices.useful_repair_urls_explanation')">
        <div class="form-control form-control__select ml-1">
          <select class="select2" name="source">
            <option value="">@lang('general.please_select')</option>
            <option value="1">@lang('devices.from_manufacturer')</option>
            <option value="2">@lang('devices.from_third_party')</option>
          </select>
        </div>
        <div class="input-group-append">
          <button class="btn btn-link" type="button"><span>+</span></button>
        </div>
      </div>
      <div class="additional-urls"></div>
    @endif
</div>
