@extends('layouts.app', ['show_login_join_to_anons' => true, 'hide_language' => false])

@section('extra-css')

@include('tabicatora/shared-css')

@endsection

@section('extra-meta')
<meta name="description" content="Help analyse faults in smartphones brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta name="keywords" content="TabiCat, TabiCat, tablets, iPad, Kindle, Samsung, Amazon Fire, e-reader, satnav, TomTom, community events, Restart Parties, Repair Cafés, repair data, Right to Repair, Open Repair Alliance, The Restart Project, Open Repair Data">
<meta property="og:title" content="TabiCat">
<meta property="og:description" content="Help analyse faults in tablets and e-readers brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta property="og:image" content="{{ asset('/images/tabicatora/og-tabicat-toolbox.png') }}">
<meta property="og:url" content="https://restarters.net/tabicat/">
@endsection

@section('title')
<?php echo $title; ?>
@endsection

@section('content')

<section class="tabicat">
    <div class="container mt-1 mt-sm-4">
        <div class="row row-compressed">
            <div class="col-6">
                <h1 class="pull-left">TabiCat @lang('tabicatora.status.status')</h1>
            </div>
            <div class="col-6 pull-right">
                <!--
            These images are licensed under the Creative Commons Attribution 4.0 International license.
            Attribution: Vincent Le Moign
            https://commons.wikimedia.org/wiki/Category:SVG_emoji_smilies
                -->
                <a id="btn-info-open"
                   data-toggle="modal" data-target="#tabicatoraInfoModal"
                   class="btn btn-info btn-sm btn-rounded p-2 pull-right">
                    <svg style="width:24px;height:24px;" viewBox="0 0 24 24">
                    <title>About TabiCat</title>
                    <path fill="#fff" d="M13.5,4A1.5,1.5 0 0,0 12,5.5A1.5,1.5 0 0,0 13.5,7A1.5,1.5 0 0,0 15,5.5A1.5,1.5 0 0,0 13.5,4M13.14,8.77C11.95,8.87 8.7,11.46 8.7,11.46C8.5,11.61 8.56,11.6 8.72,11.88C8.88,12.15 8.86,12.17 9.05,12.04C9.25,11.91 9.58,11.7 10.13,11.36C12.25,10 10.47,13.14 9.56,18.43C9.2,21.05 11.56,19.7 12.17,19.3C12.77,18.91 14.38,17.8 14.54,17.69C14.76,17.54 14.6,17.42 14.43,17.17C14.31,17 14.19,17.12 14.19,17.12C13.54,17.55 12.35,18.45 12.19,17.88C12,17.31 13.22,13.4 13.89,10.71C14,10.07 14.3,8.67 13.14,8.77Z"></path>
                    </svg></a>
                <a href="{{ '/tabicat' . ($partner ? '?partner=$partner' : '') }}" class="pull-right">
                    <img id="tabicat" src="{{ asset('/images/tabicatora/099-smiling-cat-face-with-heart-eyes-64px.svg.png') }}" alt="Go to TabiCat" width="48" height="48" />
                </a>
            </div>
        </div>
        @if(session()->has('success'))
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            You've seen them all, thanks!
        </div>
        @endif
        @if (isset($status))
        @if (!$complete)
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <p><strong>@lang('tabicatora.status.items_opinions')</strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <p class="badge-pill badge-light"><span>@lang('tabicatora.status.total')</span></p>
                        <p>
                            @php( print($status['total_devices'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>@lang('tabicatora.status.with_3_opinions')</span></p>
                        <p>
                            @php( print($status['total_opinions_3'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>@lang('tabicatora.status.with_2_opinions')</span></p>
                        <p>
                            @php( print($status['total_opinions_2'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>@lang('tabicatora.status.with_1_opinion')</span></p>
                        <p>
                            @php( print($status['total_opinions_1'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>@lang('tabicatora.status.with_0_opinions')</span></p>
                        <p>
                            @php( print($status['total_opinions_0'][0]->total))
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <p><strong>@php( print($status['progress']))% @lang('tabicatora.status.progress')</strong></p>
                </div>
             </div>
        </div>
        @endif
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <p><strong>@lang('tabicatora.status.items_with_majority_opinions') : @php( print($status['total_recats'][0]->total)) </strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <div class="row badge-pill badge-light">
                            <div class="col col-2">
                                @lang('tabicatora.status.number_of_records')
                            </div>
                            <div class="col">
                                @lang('tabicatora.status.winning_opinion')
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
                    <p><strong>@lang('tabicatora.status.items_with_split_opinions') : @php( print($status['total_splits'][0]->total))</strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <div class="row badge-pill badge-light">
                            <div class="col col-1">
                                ID
                            </div>
                            <div class="col col-3">
                                @lang('tabicatora.status.opinions')
                            </div>
                            <div class="col col-2">
                                @lang('tabicatora.status.brand')
                            </div>
                            <div class="col">
                                @lang('tabicatora.status.problem')
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
    @include('tabicatora/info-modal')
</section>

@endsection