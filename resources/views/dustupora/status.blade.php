@extends('layouts.app', ['show_navbar_to_anons' => true, 'show_login_join_to_anons' => true, 'hide_language' => false])

@section('extra-css')

@include('dustupora/shared-css')

@endsection

@section('extra-meta')
<meta name="description" content="Help analyse faults caused by vacuums in devices brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta name="keywords" content="DustUp, vacuums, community events, Restart Parties, Repair Cafés, repair data, Right to Repair, Open Repair Alliance, The Restart Project, Open Repair Data">
<meta property="og:title" content="DustUp">
<meta property="og:description" content="Help analyse faults caused by vacuums in devices brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta property="og:image" content="{{ asset('/images/dustupora/og-dustup-logo.png') }}">
<meta property="og:url" content="https://restarters.net/dustup/">
<meta property="og:type" content="website">
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
                <h1 class="pull-left">DustUp @lang('dustupora.status.status')
                    <img id="dustup" class="pull-left d-none d-lg-block" src="{{ asset('/images/dustupora/status-icon.png') }}" alt="DustUp status" />
                </h1>
            </div>
            <div class="col-7 text-right">
                <a id="btn-info-open" data-toggle="modal" data-target="#dustuporaInfoModal" class="btn btn-primary ml-2">
                    @lang('dustupora.about')
                </a>
                @if (!$complete && !$closed)
                <a class="btn btn-primary" href="{{ '/dustup' }}">
                    @lang('microtasking.cta.dustup.get_involved')
                </a>
                @endif
            </div>
        </div>
        @if(session()->has('success'))
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            @lang('dustupora.status.task_completed')!
        </div>
        @endif
        @if ($closed)
            <div class="row panel px-1 py-3 mb-4 mx-1 mx-sm-0 quest-closed">
                <div class="col text-left">
                    <h4>@lang('microtask-ora.quest-closed.header')</h4>
                    <p>
                        @lang('microtask-ora.quest-closed.message-1')
                    </p>

                    <p>
                        @lang('microtask-ora.quest-closed.message-2')
                    </p>
                    <ul>
                        <li><a href="https://talk.restarters.net/t/help-us-make-the-case-for-user-replaceable-vacuums-with-dustup/5216">@lang('microtask-ora.quest-closed.read-more', ['quest' => 'DustUp'])</a></li>
                        <li><a href="https://restarters.net/workbench">Vist the Workbench</a></li>
                    </ul>
                </div>
            </div>
        @endif
        @if (isset($status) && !$complete && !$closed)
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <strong>{{ $status['progress'][0]->total }}% @lang('dustupora.status.progress')</strong>
                </div>
            </div>
        </div>
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <strong>{{ $status['total_opinions'][0]->total }} @lang('dustupora.status.opinions')</strong>
                </div>
            </div>
        </div>
        @endif
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col text-smaller">
                <div class="row justify-content-center">
                    <p><strong>@lang('dustupora.status.items_majority_opinions') : {{ $status['total_recats'][0]->total }} </strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <div class="row badge-pill badge-light">
                            <div class="col col-3">
                                @lang('dustupora.status.number_of_records')
                            </div>
                            <div class="col">
                                @lang('dustupora.status.winning_opinion')
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
    </div>
    <div id="ora-partnership" class="mt-8 mb-4">
        <hr />
        <p class="mb-1">@lang('dustupora.branding.powered_by')</p>
        <a href="https://openrepair.org" target="_blank">
            <img src="{{ asset('images/dustupora/ora-logo.png') }}" alt="Open Repair Alliance logo" />
        </a>
    </div>
    @include('dustupora/info-modal')
</section>

@endsection
