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
        <a id="btn-cta-open"data-toggle="modal" data-target="#taskctaModal"class="hide">cta</a>
        <div class="row row-compressed">
            <div class="col-6">
                <h1 class="pull-left">TabiCat</h1>
            </div>
            <div class="col-6 pull-right">
                <!--
            These images are licensed under the Creative Commons Attribution 4.0 International license.
            Attribution: Vincent Le Moign
            https://commons.wikimedia.org/wiki/Category:SVG_emoji_smilies
                -->
                <?php
                if ($fault) {
                    switch ($fault->repair_status) {
                        case 'Fixed': $img = '099-smiling-cat-face-with-heart-eyes-64px.svg.png';
                            $alt = 'LoveCat';
                            break;
                        case 'Repairable': $img = '097-grinning-cat-face-with-smiling-eyes-64px.svg.png';
                            $alt = 'HappyCat';
                            break;
                        case 'End of life': $img = '103-crying-cat-face-64px.svg.png';
                            $alt = 'SadCat';
                            break;
                        case 'Unknown': $img = '102-weary-cat-face-64px.svg.png';
                            $alt = 'HuhCat';
                            break;
                    }
                } else {
                    $img = '100-cat-face-with-wry-smile.svg.png';
                    $alt = 'MehCat';
                }
                ?>
                <a id="btn-info-open"
                   data-toggle="modal" data-target="#tabicatoraInfoModal"
                   class="btn btn-info btn-sm btn-rounded p-2 pull-right">
                    <svg style="width:24px;height:24px;" viewBox="0 0 24 24">
                    <title>About TabiCat</title>
                    <path fill="#fff" d="M13.5,4A1.5,1.5 0 0,0 12,5.5A1.5,1.5 0 0,0 13.5,7A1.5,1.5 0 0,0 15,5.5A1.5,1.5 0 0,0 13.5,4M13.14,8.77C11.95,8.87 8.7,11.46 8.7,11.46C8.5,11.61 8.56,11.6 8.72,11.88C8.88,12.15 8.86,12.17 9.05,12.04C9.25,11.91 9.58,11.7 10.13,11.36C12.25,10 10.47,13.14 9.56,18.43C9.2,21.05 11.56,19.7 12.17,19.3C12.77,18.91 14.38,17.8 14.54,17.69C14.76,17.54 14.6,17.42 14.43,17.17C14.31,17 14.19,17.12 14.19,17.12C13.54,17.55 12.35,18.45 12.19,17.88C12,17.31 13.22,13.4 13.89,10.71C14,10.07 14.3,8.67 13.14,8.77Z"></path>
                    </svg></a>
                <a href="{{ '/tabicat/status' . ($partner ? '?partner=$partner' : '') }}" class="pull-right">
                    <img id="tabicat" src="{{ asset('/images/tabicatora/'.$img) }}" alt="{{ $alt }}" width="48" height="48" />
                </a>
            </div>
        </div>

        @if($errors->any())
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 justify-content-center">
            {{$errors->first()}}
        </div>
        @endif

        @if ($fault)
        <div class="row problem panel p-3 mb-4 mx-1 mx-sm-0 notification">
            <div class="col">
                <div class="row">
                    <div class="col">
                        <p>
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info">@php( print($fault->partner))</span>
                            @if (!empty($fault->brand))
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info">@php( print($fault->brand))</span>
                            @endif
                            @if ($fault->repair_status !== 'Unknown')
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info">@lang($fault->repair_status)</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8 offset-sm-2">
                        <p class="subtitle">
                            @php( print( $fault->problem))
                        </p>
                    </div>
                    <div class="col-4 col-sm-2">
                        <button id="btn-translate" class="pull-right btn btn-md btn-dark px-3 py-1">

                            <a href="https://translate.google.com/#view=home&op=translate&sl=@php( print($fault->language))&tl=@php( print($locale))&text=@php( print($fault->translate))" target="_blank">
                                @lang('tabicatora.task.translate')
                            </a>
                        </button>
                    </div>
                </div>
                <!--                @if (!($fault->language == 'en'))
                                <div class="row translation">
                    <div class="col-8 offset-sm-2">
                        <p class="small">
                            @php( print( $fault->translation))
                        </p>
                    </div>
                </div>
                                @endif-->
            </div>
        </div>
        <form id="log-task" action="" method="POST">
            @csrf
            <div class="container fault-type">
                <div class="row">
                    <div class="col panel p-3">
                        <p><span class="question">@lang('tabicatora.task.where_is_the_main_fault')?</span></p>
                        <div class="container">
                            <input type="hidden" id="id-ords" name="id-ords" value="@php( print($fault->id_ords))">
                            <input type="hidden" id="fault-type-id" name="fault-type-id" value="">
                            @if (count($fault->suggestions))
                            <div class="buttons suggestions">
                                <p class="title is-size-6-mobile is-size-6-tablet">@lang('tabicatora.task.suggestions')</p>
                                <p>
                                    @foreach($fault->suggestions as $fault_type)
                                    <!--<button class="btn btn-sm btn-fault-suggestion btn-success btn-rounded" data-toggle="tooltip"title="@php( print($fault_type->description) )"><span data-fid="@php( print($fault_type->id) )" >@lang($fault_type->title)</span></button>-->
                                    <button class="btn btn-sm btn-fault-suggestion btn-success btn-rounded" data-toggle="tooltip" data-fid="@php( print($fault_type->id) )" title="@php( print($fault_type->description) )">@lang($fault_type->title)</button>
                                    @endforeach
                                </p>
                            </div>
                            @endif
                            <div class="container options mb-3">
                                <p class="confirm hide">
                                    <button class="btn-md btn-info btn-rounded" id="change">@lang('tabicatora.task.go_with') "<span id="fault-type-new" data-fid=""></span>"</button>
                                </p>
                                <div class="buttons">
                                    @foreach($fault->faulttypes as $fault_type)
                                    <button class="btn btn-sm btn-fault-option btn-rounded" data-toggle="tooltip" data-fid="@php( print($fault_type->id) )" title="@php( print($fault_type->description) )">@lang($fault_type->title)</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <button type="submit" name="fetch" id="fetch" class="btn btn-md btn-warning btn-rounded my-4">
            <span class="">@lang('tabicatora.task.fetch_another')</span>
        </button>
        @endif
    </div>
    @include('tabicatora/info-modal')
    @include('partials/task-cta-ora-modal')
</section>

@endsection

@section('scripts')
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {

        [...document.querySelectorAll('.btn-fault-option, .btn-fault-suggestion')].forEach(elem => {
            elem.addEventListener('click', function (e) {
                e.preventDefault();
                doOption(e);
            });
        });
        document.getElementById('change').addEventListener('click', function (e) {
            e.preventDefault();
            doChange();
        }, false);
        document.getElementById('fetch').addEventListener('click', function (e) {
            e.preventDefault();
            fetchNew();
        }, false);
        document.addEventListener("keypress", function (e) {
            if (e.code == 'KeyF') {
                e.preventDefault();
                document.getElementById('fetch').click();
            } else if (e.code == 'KeyG') {
                e.preventDefault();
                doChange();
            } else if (e.code == 'KeyI') {
                e.preventDefault();
                document.getElementById('btn-info-open').click();
            } else if (e.code == 'KeyC') {
                e.preventDefault();
                document.getElementById('btn-cta-open').click();
            }
        }, false);
        function doOption(e) {
            document.getElementById('fault-type-new').innerText = e.target.innerText;
            document.getElementById('fault-type-new').dataset.fid = e.target.dataset.fid;
            document.querySelector('.confirm').classList.replace('hide', 'show');
            document.getElementById('change').focus({
                preventScroll: false
            });
        }

        function doChange(e) {
            if (document.getElementById('fault-type-new').dataset.fid !== '') {
                document.getElementById('fault-type-id').value = document.getElementById('fault-type-new').dataset.fid;
                submitFormLog();
            }
        }

        function submitFormLog() {
            let fid = document.getElementById('fault-type-id').value;
            let oid = document.getElementById('id-ords').value;
            if (!fid) {
                fetchNew();
            } else if (!oid) {
                fetchNew();
            } else {
                console.log('submitForm - id-ords ' + oid + ' / fault-type-id: ' + fid);
                document.forms['log-task'].submit();
            }
        }
        function fetchNew() {
            window.location.replace(window.location.href);
        }

        if (window.location.href.indexOf('cta') != -1) {
            document.getElementById('btn-cta-open').click();
        }

    }, false);</script>

@endsection
