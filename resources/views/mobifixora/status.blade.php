@extends('layouts.app', ['show_navbar_to_anons' => true, 'show_login_join_to_anons' => true, 'hide_language' => true])

@section('extra-css')

@include('mobifixora/shared-css')

@endsection

@section('extra-meta')
<meta name="description" content="Help analyse faults in smartphones brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta name="keywords" content="MobiFix:ORA, MobiFix, smartphones, mobiles, handi iPhone, Samsung Galaxy, community events, Restart Parties, Repair Cafés, repair data, Right to Repair, Open Repair Alliance, The Restart Project, Open Repair Data, FixFest">
<meta property="og:title" content="MobiFix:ORA">
<meta property="og:description" content="Help analyse faults in smartphones brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta property="og:image" content="{{ asset('/images/mobifix/og-mobifix-toolbox.png') }}">
<meta property="og:url" content="https://restarters.net/mobifixora/">
@endsection

@section('title')
<?php echo $title; ?>
@endsection

@section('content')

<section class="mobifix">
    <div class="container mt-1 mt-sm-4">
        <div class="row row-compressed">
            <div class="col-6">
                <h1 class="pull-left">MobiFix:ORA Status</h1>
            </div>
            <div class="col-6 pull-right">
                <!--
            These images are licensed under the Creative Commons Attribution 4.0 International license.
            Attribution: Vincent Le Moign
            https://commons.wikimedia.org/wiki/Category:SVG_emoji_smilies
                -->
                <a id="btn-info-open"
                   data-toggle="modal" data-target="#mobifixoraInfoModal"
                   class="btn btn-info btn-sm btn-rounded p-2">
                    <svg style="width:24px;height:24px;" viewBox="0 0 24 24">
                    <title>About MobiFix:ORA</title>
                    <path fill="#fff" d="M13.5,4A1.5,1.5 0 0,0 12,5.5A1.5,1.5 0 0,0 13.5,7A1.5,1.5 0 0,0 15,5.5A1.5,1.5 0 0,0 13.5,4M13.14,8.77C11.95,8.87 8.7,11.46 8.7,11.46C8.5,11.61 8.56,11.6 8.72,11.88C8.88,12.15 8.86,12.17 9.05,12.04C9.25,11.91 9.58,11.7 10.13,11.36C12.25,10 10.47,13.14 9.56,18.43C9.2,21.05 11.56,19.7 12.17,19.3C12.77,18.91 14.38,17.8 14.54,17.69C14.76,17.54 14.6,17.42 14.43,17.17C14.31,17 14.19,17.12 14.19,17.12C13.54,17.55 12.35,18.45 12.19,17.88C12,17.31 13.22,13.4 13.89,10.71C14,10.07 14.3,8.67 13.14,8.77Z"></path>
                    </svg></a>
                @php( $back = '/mobifixora' . ($partner ? "?partner=$partner" : '') )
                <!-- <a href="{{ $back }}">
                    <img id="mobifix" src="{{ asset('/images/mobifix/whale-spouting.png') }}" alt="Go to MobiFix:ORA" width="48" height="48" />
                </a> -->
            </div>
        </div>
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 quest-closed">
            <div class="col text-left">
                <h4 class="">This quest is complete 🎉</h4>
                <p>
                    Thank you for your interest in this quest.
                    We have now received enough responses.
                    Instead, why not see what we learned or try another quest?
                </p>
                <ul>
                    <li><a href="https://openrepair.org/open-data/insights/mobiles/">Discover what we learned and download the data</a></li>
                    <li><a href="https://restarters.net/workbench">See our other quests</a></li>
                </ul>
            </div>
        </div>
        @if (isset($status))
        @if (!$complete)
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <p><strong>Items / opinions</strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <p class="badge-pill badge-light"><span>Total</span></p>
                        <p>
                            @php( print($status['total_devices'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>with 3 opinions</span></p>
                        <p>
                            @php( print($status['total_opinions_3'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>with 2 opinions</span></p>
                        <p>
                            @php( print($status['total_opinions_2'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>with 1 opinion</span></p>
                        <p>
                            @php( print($status['total_opinions_1'][0]->total))
                        </p>
                    </div>
                    <div class="col">
                        <p class="badge-pill badge-light"><span>with 0 opinions</span></p>
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
                    <p><strong>Items with split opinions : @php( print($status['total_splits'][0]->total))</strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <div class="row badge-pill badge-light">
                            <div class="col col-1">
                                ID
                            </div>
                            <div class="col col-3">
                                Opinions
                            </div>
                            <div class="col col-2">
                                Brand
                            </div>
                            <div class="col col-2">
                                Model
                            </div>
                            <div class="col">
                                Problem
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center small">
                    <div class="col">
                        @foreach($status['list_splits'] as $row)
                        <div class="row border-grey">
                            <div class="col col-1">
                                {{ $row->id_ords ?? "" }}
                            </div>
                            <div class="col col-3">
                                {{ $row->opinions ?? "" }}
                            </div>
                            <div class="col col-2">
                                {{ $row->brand ?? "" }}
                            </div>
                            <div class="col col-2">
                                {{ $row->model ?? "" }}
                            </div>
                            <div class="col">
                                {{ $row->problem ?? "" }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            <div class="col">
                <div class="row justify-content-center">
                    <p><strong>Items with majority opinions : @php( print($status['total_recats'][0]->total)) </strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <div class="row badge-pill badge-light">
                            <div class="col col-2">
                                Number of records
                            </div>
                            <div class="col">
                                Winning opinion
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
    </div>
    @include('mobifixora/info-modal')
</section>

@endsection

@section('scripts')

@endsection
