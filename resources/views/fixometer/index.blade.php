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
                    :latest-data="{{ json_encode($most_recent_finished_event) }}"
                    :impact-data="{{ json_encode($impact_data) }}"
                    :clusters="{{ json_encode($clusters) }}"
                    :brands="{{ json_encode($brands) }}"
                    :barrier-list="{{ json_encode($barriers) }}"
                />
            </div>
        </div>
</section>

@endsection