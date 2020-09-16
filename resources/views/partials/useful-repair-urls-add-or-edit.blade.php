<div class="form-group useful-repair-urls">
    @foreach( $urls as $url )
        <div class="input-group" data-device_id="{{{ $device->iddevices }}}" data-id="{{{ $url->id }}}">
            <div class="mb-2 device-select-row w-100">
                <div>
                    <input @if( !$editable ) disabled @endif name="url" value="{{{ $url->url }}}" type="url" class="form-control w-100" placeholder="@lang('devices.repair_url')" aria-label="@lang('devices.useful_repair_urls_explanation')">
                    <div class="form-control form-control__select mt-2">
                        <select @if( !$editable ) disabled @endif class="select2" name="source" data-placeholder="@lang('general.please_select')">
                            <option></option>
                            <option value="1" @if ($url->source == 1) selected @endif>@lang('devices.from_manufacturer')</option>
                            <option value="2" @if ($url->source == 2) selected @endif>@lang('devices.from_third_party')</option>
                        </select>
                    </div>
                </div>
                <div class="save-url">
                    @if ($editable)
                        <button class="btn btn-link ml-1" type="button">
                            <svg width="20" height="20" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><g opacity="0.5"><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></g></svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
    @if ($editable)
        <div class="input-group" data-device_id="{{{ $device->iddevices }}}">
            <div class="mb-2 device-select-row w-100">
                <div>
                    <input type="url" name="url" class="form-control" placeholder="@lang('devices.useful_repair_urls_explanation')" aria-label="@lang('devices.useful_repair_urls_explanation')">
                    <div class="form-control form-control__select mt-2">
                        <select class="select2" name="source" data-placeholder="@lang('devices.repair_source')">
                            <option></option>
                            <option value="1">@lang('devices.from_manufacturer')</option>
                            <option value="2">@lang('devices.from_third_party')</option>
                        </select>
                    </div>
                </div>
                <div class="add-url">
                    @if ($edit)
                        <button class="btn btn-link" type="button">
                            <img style="width:20px;height:20px" class="icon" src="/images/add-icon.svg" />
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="additional-urls"></div>
    @endif
</div>
