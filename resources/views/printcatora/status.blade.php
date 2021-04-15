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
    <div class="container mt-1 mt-sm-4">
        <div class="row row-compressed">
            <div class="col-6">
                <h1 class="pull-left">PrintCat @lang('printcatora.status.status')</h1>
            </div>
            <div class="col-6 text-right">
                <!--
            These images are licensed under the Creative Commons Attribution 4.0 International license.
            Attribution: Vincent Le Moign
            https://commons.wikimedia.org/wiki/Category:SVG_emoji_smilies
                -->
                <a id="btn-info-open"
                   data-toggle="modal" data-target="#printcatoraInfoModal"
                   class="btn btn-info btn-sm btn-rounded p-2 pull-right">
                    <svg style="width:24px;height:24px;" viewBox="0 0 24 24">
                    <title>About PrintCat</title>
                    <path fill="#fff" d="M13.5,4A1.5,1.5 0 0,0 12,5.5A1.5,1.5 0 0,0 13.5,7A1.5,1.5 0 0,0 15,5.5A1.5,1.5 0 0,0 13.5,4M13.14,8.77C11.95,8.87 8.7,11.46 8.7,11.46C8.5,11.61 8.56,11.6 8.72,11.88C8.88,12.15 8.86,12.17 9.05,12.04C9.25,11.91 9.58,11.7 10.13,11.36C12.25,10 10.47,13.14 9.56,18.43C9.2,21.05 11.56,19.7 12.17,19.3C12.77,18.91 14.38,17.8 14.54,17.69C14.76,17.54 14.6,17.42 14.43,17.17C14.31,17 14.19,17.12 14.19,17.12C13.54,17.55 12.35,18.45 12.19,17.88C12,17.31 13.22,13.4 13.89,10.71C14,10.07 14.3,8.67 13.14,8.77Z"></path>
                    </svg></a>
                <a href="{{ '/printcat' . ($partner ? '?partner=$partner' : '') }}" class="pull-right">
                    <img id="printcat" src="{{ asset('/images/printcatora/099-smiling-cat-face-with-heart-eyes-64px.svg.png') }}" alt="Go to PrintCat" width="48" height="48" />
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
    @include('printcatora/info-modal')
</section>

@endsection
