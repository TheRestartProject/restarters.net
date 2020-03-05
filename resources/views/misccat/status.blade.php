@extends('layouts.app', ['show_login_join_to_anons' => true])

@section('extra-css')

@include('misccat/shared-css')

@endsection

@section('content')

<section class="misccat">
    <div class="container mt-1 mt-sm-4">
        <a id="btn-cta-open"data-toggle="modal" data-target="#taskctaModal"class="hide">cta</a>
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
                <a id="btn-info-open"
                   data-toggle="modal" data-target="#misccatInfoModal"
                   class="btn btn-info btn-sm btn-rounded p-2">
                    <svg style="width:24px;height:24px;" viewBox="0 0 24 24">
                    <title>About MiscCat</title>
                    <path fill="#fff" d="M13.5,4A1.5,1.5 0 0,0 12,5.5A1.5,1.5 0 0,0 13.5,7A1.5,1.5 0 0,0 15,5.5A1.5,1.5 0 0,0 13.5,4M13.14,8.77C11.95,8.87 8.7,11.46 8.7,11.46C8.5,11.61 8.56,11.6 8.72,11.88C8.88,12.15 8.86,12.17 9.05,12.04C9.25,11.91 9.58,11.7 10.13,11.36C12.25,10 10.47,13.14 9.56,18.43C9.2,21.05 11.56,19.7 12.17,19.3C12.77,18.91 14.38,17.8 14.54,17.69C14.76,17.54 14.6,17.42 14.43,17.17C14.31,17 14.19,17.12 14.19,17.12C13.54,17.55 12.35,18.45 12.19,17.88C12,17.31 13.22,13.4 13.89,10.71C14,10.07 14.3,8.67 13.14,8.77Z"></path>
                    </svg></a>
                <a href="/misccat">
                    <img id="misccat" src="{{ asset('/images/misccat/100-cat-face-with-wry-smile.svg.png') }}" alt="Go to MiscCat" width="48" height="48" />
                </a>
            </div>
        </div>
        <?php if ($status) { ?>            
            <div class="row problem p-2 mb-2 mx-1 mx-sm-0 justify-content-center">
                <div class="col">
                    <div class="row justify-content-center">
                        <p><strong>Items categorised "Miscellaneous"</strong></p>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col">
                            <p><span>Total</span></p>
                            <p>
                                <?php echo($status['total_devices'][0]->total); ?>
                            </p>
                        </div>
                        <div class="col">
                            <p><span>with 3 opinions</span></p>
                            <p>
                                <?php echo($status['total_opinions_3'][0]->total); ?>
                            </p>
                        </div>
                        <div class="col">
                            <p><span>with 2 opinions</span></p>
                            <p>
                                <?php echo($status['total_opinions_2'][0]->total); ?>
                            </p>
                        </div>
                        <div class="col">
                            <p><span>with 1 opinion</span></p>
                            <p>
                                <?php echo($status['total_opinions_1'][0]->total); ?>
                            </p>
                        </div>
                        <div class="col">
                            <p><span>with 0 opinions</span></p>
                            <p>
                                <?php echo($status['total_opinions_0'][0]->total); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
    @include('misccat/info-modal')
    @include('partials/task-cta-modal')
</section>

@endsection

@section('scripts')

@endsection
