@extends('layouts.app', ['show_navbar_to_anons' => true, 'show_login_join_to_anons' => true, 'hide_language' => false])

@section('extra-css')

@include('printcatora/shared-css')

@endsection

@section('extra-meta')
<meta name="description" content="Help analyse faults in printers brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta name="keywords" content="PrintCat, printers, scanners, laserjet, inkjet, community events, Restart Parties, Repair Cafés, repair data, Right to Repair, Open Repair Alliance, The Restart Project, Open Repair Data">
<meta property="og:title" content="PrintCat">
<meta property="og:description" content="Help analyse faults in printers  brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta property="og:image" content="{{ asset('/images/printcatora/og-printcat-toolbox.png') }}">
<meta property="og:url" content="https://restarters.net/printcat/">
@endsection

@section('title')
<?php echo $title; ?>
@endsection

@section('content')

<section class="printcat">
    <div class="container mt-1 mt-sm-2">
        <div class="row row-compressed align-items-center">
            <div class="col-5">
                <h1 class="pull-left">PrintCat @lang('printcatora.status.status')
                    <img id="printcat" class="pull-left d-none d-lg-block" src="{{ asset('/images/printcatora/paw-prints.png') }}" alt="PrintCat status" />
                </h1>
            </div>
            <div class="col-7 text-right">
                <a id="btn-info-open"
                   data-toggle="modal" data-target="#printcatoraInfoModal"
                   class="btn btn-primary ml-2">
                    @lang('printcatora.about')
                </a>
                <a class="btn btn-primary" href="{{ '/printcat' . ($partner ? '?partner=$partner' : '') }}">
                    @lang('microtasking.cta.printcat.get_involved')
                </a>
            </div>
        </div>
        @if(session()->has('success'))
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            @lang('printcatora.status.task_completed')!
        </div>
        @endif
        @if (isset($status))
        @if (!$complete)
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <p><strong>@lang('printcatora.status.items_opinions')</strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <p class="badge-pill badge-light"><span>@lang('printcatora.status.total')</span></p>
                        <p>
                            @php( print($status['total_devices'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>@lang('printcatora.status.items_3_opinions')</span></p>
                        <p>
                            @php( print($status['total_opinions_3'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>@lang('printcatora.status.items_2_opinions')</span></p>
                        <p>
                            @php( print($status['total_opinions_2'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>@lang('printcatora.status.items_1_opinion')</span></p>
                        <p>
                            @php( print($status['total_opinions_1'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>@lang('printcatora.status.items_0_opinions')</span></p>
                        <p>
                            @php( print($status['total_opinions_0'][0]->total))
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <p><strong>@lang('printcatora.status.items_majority_opinions') : @php( print($status['total_recats'][0]->total))</strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <div class="row badge-pill badge-light">
                            <div class="col col-2">
                                @lang('printcatora.status.number_of_records')
                            </div>
                            <div class="col">
                                @lang('printcatora.status.winning_opinion')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center small">
                    <div class="col">
                        @foreach($status['list_recats'] as $row)
                        <div class="row border-grey">
                            <div class="col col-2">
                                @php( print($row->total) )
                            </div>
                            <div class="col">
                                @php( print($row->winning_opinion) )
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if (!$complete)
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <p><strong>@lang('printcatora.status.items_split_opinions') : @php( print($status['total_splits'][0]->total))</strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <div class="row badge-pill badge-light">
                            <div class="col col-1">
                                ID
                            </div>
                            <div class="col col-3">
                                @lang('printcatora.status.opinions')
                            </div>
                            <div class="col col-2">
                                @lang('printcatora.status.brand')
                            </div>
                            <div class="col">
                                @lang('printcatora.status.problem')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center small">
                    <div class="col">
                        @foreach($status['list_splits'] as $row)
                        <div class="row border-grey">
                            <div class="col col-1">
                                @php( print($row->id_ords) )
                            </div>
                            <div class="col col-3">
                                @php( print($row->opinions) )
                            </div>
                            <div class="col col-2">
                                @php( print($row->brand) )
                            </div>
                            <div class="col">
                                @php( print($row->problem) )
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    <div id="ora-partnership" class="mt-8 mb-4">
        <hr />
        <p class="mb-1">@lang('printcatora.branding.powered_by')</p>
        <a href="https://openrepair.org" target="_blank">
            <img src="{{ asset('images/printcatora/ora-logo.png') }}" alt="Open Repair Alliance logo" />
        </a>
    </div>
    @include('printcatora/info-modal')
</section>

@endsection
