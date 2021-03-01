@extends('layouts.app')

@section('content')

<section class="devices">
    <div class="container">
        <div class="vue-placeholder vue-placeholder-large">
            <div class="vue-placeholder-content">@lang('partials.loading')...</div>
        </div>
        <div class="vue">
            <DeviceEditPage
                :is-admin="{{ FixometerHelper::hasRole($user, 'Administrator') ? 'true' : 'false' }}"
                csrf="{{ csrf_token() }}"
                :initial-device="{{ json_encode($device, JSON_INVALID_UTF8_IGNORE) }}"
                :brands="{{ json_encode($brands, JSON_INVALID_UTF8_IGNORE) }}"
                :clusters="{{ json_encode($clusters, JSON_INVALID_UTF8_IGNORE) }}"
                :barrier-list="{{ json_encode(FixometerHelper::allBarriers(), JSON_INVALID_UTF8_IGNORE) }}"
            />
        </div>
    </div>
</section>

@endsection
