<div class="form-group useful-repair-urls">
    <label>@lang('devices.useful_repair_urls'):</label>
    @foreach( $urls as $url )
    <div class="input-group save-url" data-device_id="{{{ $device->iddevices }}}" data-id="{{{ $url->id }}}">
      <input value="{{{ $url->url }}}" type="url" class="form-control" placeholder="@lang('devices.useful_repair_urls_helper')" aria-label="@lang('devices.useful_repair_urls_explanation')">
      @if(FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($device->event, Auth::user()->id) )
      <div class="input-group-append">
        <button class="btn btn-link" type="button"><span>-</span></button>
      </div>
      @endif
    </div>
    @endforeach
    @if(FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($device->event, Auth::user()->id) )
      <div class="input-group add-url" data-device_id="{{{ $device->iddevices }}}">
        <input type="url" class="form-control" placeholder="@lang('devices.useful_repair_urls_helper')" aria-label="@lang('devices.useful_repair_urls_explanation')">
        <div class="input-group-append">
          <button class="btn btn-link" type="button"><span>+</span></button>
        </div>
      </div>
      <div class="additional-urls"></div>
    @endif
</div>
