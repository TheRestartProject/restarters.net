@extends('layouts.app', ['show_navbar_to_anons' => true, 'show_login_join_to_anons' => true, 'hide_language' => true])

@section('extra-css')

@include('misccat/shared-css')

@endsection

@section('content')

<section class="misccat">
    <div class="container mt-1 mt-sm-4">
        <a id="btn-cta-open" data-toggle="modal" data-target="#taskctaModal" class="hide">cta</a>
        <div class="row row-compressed">
            <div class="col-6">
                <h1 class="pull-left">MiscCat Status</h1>
            </div>
            <div class="col-6 pull-right">
                <!--
            These images are licensed under the Creative Commons Attribution 4.0 International license.
            Attribution: Vincent Le Moign
            https://commons.wikimedia.org/wiki/Category:SVG_emoji_smilies
                -->
                <a id="btn-info-open" data-toggle="modal" data-target="#misccatInfoModal" class="btn btn-info btn-sm btn-rounded p-2">
                    <svg style="width:24px;height:24px;" viewBox="0 0 24 24">
                        <title>About MiscCat</title>
                        <path fill="#fff" d="M13.5,4A1.5,1.5 0 0,0 12,5.5A1.5,1.5 0 0,0 13.5,7A1.5,1.5 0 0,0 15,5.5A1.5,1.5 0 0,0 13.5,4M13.14,8.77C11.95,8.87 8.7,11.46 8.7,11.46C8.5,11.61 8.56,11.6 8.72,11.88C8.88,12.15 8.86,12.17 9.05,12.04C9.25,11.91 9.58,11.7 10.13,11.36C12.25,10 10.47,13.14 9.56,18.43C9.2,21.05 11.56,19.7 12.17,19.3C12.77,18.91 14.38,17.8 14.54,17.69C14.76,17.54 14.6,17.42 14.43,17.17C14.31,17 14.19,17.12 14.19,17.12C13.54,17.55 12.35,18.45 12.19,17.88C12,17.31 13.22,13.4 13.89,10.71C14,10.07 14.3,8.67 13.14,8.77Z"></path>
                    </svg></a>
                <!-- <a href="/misccat">
                    <img id="misccat" src="{{ asset('/images/misccat/100-cat-face-with-wry-smile.svg.png') }}" alt="Go to MiscCat" width="48" height="48" />
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
                    <li><a href="https://talk.restarters.net/t/open-data-day-get-involved-in-repair-data-with-misccat/2601">Discover what we learned</a></li>
                    <li><a href="https://restarters.net/workbench">See our other quests</a></li>
                </ul>
            </div>
        </div>
        @if (isset($status))
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0">
            <div class="col">
                <div class="row justify-content-center">
                    <p><strong>Items with category changed from "Misc"</strong></p>
                </div>
                <div class="row justify-content-center">
                    <div class="col">
                        <div class="row badge-pill badge-light">
                            <div class="col col-5 text-right">
                                Total
                            </div>
                            <div class="col text-left">
                                Top opinion
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center small">
                    <div class="col">
                        <?php $i = 0; ?>
                        @foreach ($status['list_recats'] as $row)
                        <?php $i = $i + $row->items; ?>
                        <div class="row border-grey">
                            <div class="col col-5 text-right">
                                @php( print($row->items) )
                            </div>
                            <div class="col text-left">
                                @php( print($row->top_opinion) )
                            </div>
                        </div>
                        @endforeach
                        <div class="row border-grey">
                            <div class="col col-5 text-right">
                                <strong><?php print($i); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @include('misccat/info-modal')
    @include('partials/task-cta-modal')
</section>

@endsection

@section('scripts')

@endsection