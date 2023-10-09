@extends('layouts.app')

@section('title')
@lang('devices.fixometer')
@endsection

@section('content')

<section class="devices">
        <div class="container">

            <div class="vue-placeholder vue-placeholder-large">
                <div class="vue-placeholder-content">@lang('partials.loading')...</div>
            </div>

            <div class="vue">
                <FixometerPage
                    csrf="{{ csrf_token() }}"
                    :latest-data="{{ json_encode($most_recent_finished_event, JSON_INVALID_UTF8_IGNORE) }}"
                    :impact-data="{{ json_encode($impact_data, JSON_INVALID_UTF8_IGNORE) }}"
                    :clusters="{{ json_encode($clusters, JSON_INVALID_UTF8_IGNORE) }}"
                    :brands="{{ json_encode($brands, JSON_INVALID_UTF8_IGNORE) }}"
                    :barrier-list="{{ json_encode($barriers, JSON_INVALID_UTF8_IGNORE) }}"
                    :is-admin="{{ App\Helpers\Fixometer::hasRole($user, 'Administrator') ? 'true' : 'false' }}"
                />
            </div>
        </div>
</section>

@endsection

@include('fixometer.add-data-modal')