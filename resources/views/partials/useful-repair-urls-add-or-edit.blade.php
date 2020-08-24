<div class="form-group useful-repair-urls">
    @foreach( $urls as $url )
        <div class="input-group save-url" data-device_id="{{{ $device->iddevices }}}" data-id="{{{ $url->id }}}">
            <input @if( !Auth::check() ) disabled @endif value="{{{ $url->url }}}" type="url" class="form-control mr-1" placeholder="@lang('devices.useful_repair_urls_helper')" aria-label="@lang('devices.useful_repair_urls_explanation')">
            <div class="form-control form-control__select ml-1">
                <select @if( !Auth::check() ) disabled @endif class="select2" name="source">
                    <option value="">@lang('general.please_select')</option>
                    <option value="1" @if ($url->source == 1) selected @endif>@lang('devices.from_manufacturer')</option>
                    <option value="2" @if ($url->source == 2) selected @endif>@lang('devices.from_third_party')</option>
                </select>
            </div>
            @if( Auth::check() && ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($device->event, Auth::user()->id) ) )
                <div class="input-group-append">
                    <button class="btn btn-link" type="button"><span>-</span></button>
                </div>
            @endif
        </div>
    @endforeach
    @if( Auth::check() && ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($device->event, Auth::user()->id) ) )
        <div class="input-group add-url" data-device_id="{{{ $device->iddevices }}}">
            <input type="url" class="form-control w-100" placeholder="@lang('devices.repair_source')" aria-label="@lang('devices.useful_repair_urls_explanation')">
            <div class="form-control form-control__select w-100 mt-2">
                <select class="select2" name="source">
                    <option value="">@lang('devices.repair_url')</option>
                    <option value="1">@lang('devices.from_manufacturer')</option>
                    <option value="2">@lang('devices.from_third_party')</option>
                </select>
            </div>
            <button class="btn btn-link text-black" type="button"><span>+</span></button>
        </div>
        <div class="additional-urls"></div>
    @endif
</div>
