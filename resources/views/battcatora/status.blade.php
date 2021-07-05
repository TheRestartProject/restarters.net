@extends('layouts.app', ['show_navbar_to_anons' => true, 'show_login_join_to_anons' => true, 'hide_language' => false])

@section('extra-css')

@include('battcatora/shared-css')

@endsection

@section('extra-meta')
<meta name="description" content="Help analyse faults in smartphones brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta name="keywords" content="BattCat, tablets, iPad, Kindle, Samsung, Amazon Fire, e-reader, satnav, TomTom, community events, Restart Parties, Repair Cafés, repair data, Right to Repair, Open Repair Alliance, The Restart Project, Open Repair Data">
<meta property="og:title" content="BattCat">
<meta property="og:description" content="Help analyse faults in tablets and e-readers brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta property="og:image" content="{{ asset('/images/battcatora/og-battcat-toolbox.png') }}">
<meta property="og:url" content="https://restarters.net/battcat/">
@endsection

@section('title')
<?php echo $title; ?>
@endsection
<style>
    .text-small {
        font-size: small;
    }

    .text-smaller {
        font-size: smaller;
    }
</style>
@section('content')

<section class="battcat">
    <div class="container mt-1 mt-sm-4">
        <div class="row row-compressed align-items-center">
            <div class="col-5">
                <h1 class="pull-left">BattCat @lang('battcatora.status.status')
                    <img id="battcat" class="pull-left d-none d-lg-block" src="{{ asset('/images/battcatora/paw-prints.png') }}" alt="BattCat status" />
                </h1>
            </div>
            <div class="col-7 text-right">
                <a id="btn-info-open" data-toggle="modal" data-target="#battcatoraInfoModal" class="btn btn-primary ml-2">
                    @lang('battcatora.about')
                </a>
                <a class="btn btn-primary" href="{{ '/battcat' }}">
                    @lang('microtasking.cta.battcat.get_involved')
                </a>
            </div>
        </div>
        @if(session()->has('success'))
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            @lang('battcatora.status.task_completed')!
        </div>
        @endif
        @if (isset($status) && !$complete)
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <strong>{{ $status['progress'][0]->total }}% @lang('battcatora.status.progress')</strong>
                </div>
            </div>
        </div>
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <strong>{{ $status['total_opinions'][0]->total }} @lang('battcatora.status.opinions')</strong>
                </div>
            </div>
        </div>
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col text-smaller">
                <div class="row justify-content-center">
                    <p><strong>@lang('battcatora.status.items_majority_opinions') : {{ $status['total_recats'][0]->total }} </strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <div class="row badge-pill badge-light">
                            <div class="col col-3">
                                @lang('battcatora.status.number_of_records')
                            </div>
                            <div class="col">
                                @lang('battcatora.status.winning_opinion')
                            </div>
                            <div class="col col-2">
                                @lang('devices.repair_status')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        @foreach($status['list_recats'] as $row)
                        <div class="row border border-grey">
                            <div class="col col-3">
                                {{ $row->total }}
                            </div>
                            <div class="col">
                                @lang($row->winning_opinion)
                            </div>
                            <div class="col col-2">
                                @lang($row->repair_status)
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <!-- test -->
        <div class="row justify-content-center">
            <p><strong>** ORDS product category language tester **</strong></p>
        </div>
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                @foreach($categories as $row)
                <div class="row border border-grey">
                    <div class="col">
                        {{ $row }}
                    </div>
                    <div class="col">
                        @lang($row)
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <!-- test -->
        @endif
    </div>
    <div id="ora-partnership" class="mt-8 mb-4">
        <hr />
        <p class="mb-1">@lang('battcatora.branding.powered_by')</p>
        <a href="https://openrepair.org" target="_blank">
            <img src="{{ asset('images/battcatora/ora-logo.png') }}" alt="Open Repair Alliance logo" />
        </a>
    </div>
    @include('battcatora/info-modal')
</section>

@endsection