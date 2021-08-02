<div class="form-group useful-repair-urls">
  <div class="d-flex">
    <label>@lang('devices.useful_repair_urls'):</label>
    <label>@lang('devices.useful_repair_info'):</label>
  </div>
    @foreach( $urls as $url )
    <div class="input-group save-url" data-device_id="{{{ $device->iddevices }}}" data-id="{{{ $url->id }}}">
      <input @if( !Auth::check() ) disabled @endif value="{{{ $url->url }}}" type="url" class="form-control mr-1" placeholder="@lang('devices.useful_repair_urls_helper')" aria-label="@lang('devices.useful_repair_urls_explanation')">
      <div class="form-control form-control__select ml-1">
        <select @if( !Auth::check() ) disabled @endif class="select2" name="source" data-placeholder="@lang('general.please_select')">
          <option></option>
          <option value="1" @if ($url->source == 1) selected @endif>@lang('devices.from_manufacturer')</option>
          <option value="2" @if ($url->source == 2) selected @endif>@lang('devices.from_third_party')</option>
        </select>
      </div>
      @if( Auth::check() && ( App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') || App\Helpers\Fixometer::userHasEditPartyPermission($device->event, Auth::user()->id) ) )
      <div class="input-group-append">
        <button class="btn btn-link" type="button"><span>-</span></button>
      </div>
      @endif
    </div>
    @endforeach
    @if( Auth::check() && ( App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') || App\Helpers\Fixometer::userHasEditPartyPermission($device->event, Auth::user()->id) ) )
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
