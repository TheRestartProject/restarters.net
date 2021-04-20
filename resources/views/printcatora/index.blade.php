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
            <div class="col-5 " >
                <h1>PrintCat
                    <img id="printcat" class="pull-left d-none d-sm-block" src="{{ asset('/images/printcatora/paw-prints.png') }}" alt="PrintCat status" />
                </h1>
            </div>
            <div class="col-7 text-right">
                <a id="btn-info-open"
                   data-toggle="modal" data-target="#printcatoraInfoModal"
                   class="btn btn-primary ml-2">
                    @lang('printcatora.about')
                </a>
                <a class="btn btn-primary " href="{{ '/printcat/status' . ($partner ? '?partner=$partner' : '') }}">
                    @lang('printcatora.status.status')
                </a>
            </div>
            <div class="col-12 text-left">
                <p>@lang('printcatora.task.strapline')
                    <a href="javascript:void(0);" id="a-info-open" data-toggle="modal" data-target="#printcatoraInfoModal">@lang('printcatora.task.learn_more')</a>
                </p>
            </div>
        </div>
        <a id="btn-cta-open"data-toggle="modal" data-target="#taskctaModal"class="hide">cta</a>

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
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info">@lang('printcatora.task.source'): @php( print($fault->partner))</span>
                            @if (!empty($fault->brand && $fault->brand !== 'Unknown'))
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
                                @lang('printcatora.task.translate')
                            </a>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <form id="log-task" action="" method="POST">
            @csrf
            <div class="container fault-type">
                <div class="row">
                    <div class="col panel p-3">
                        <p><span class="question">@lang('printcatora.task.where_is_the_main_fault')?</span></p>
                        <div class="container">
                            <input type="hidden" id="id-ords" name="id-ords" value="@php( print($fault->id_ords))">
                            <input type="hidden" id="fault-type-id" name="fault-type-id" value="">
                            @if (count($fault->suggestions))
                            <div class="buttons suggestions">
                                <p class="title is-size-6-mobile is-size-6-tablet">@lang('printcatora.task.suggestions')</p>
                                <p>
                                    @foreach($fault->suggestions as $fault_type)
                                    <button class="btn btn-sm btn-fault-suggestion btn-success btn-rounded" data-toggle="tooltip" data-fid="@php( print($fault_type->id) )">@lang($fault_type->title)</button>
                                    @endforeach
                                </p>
                            </div>
                            @endif
                            <div class="container options mb-3">
                                <p class="confirm hide">
                                    <button class="btn-md btn-info btn-rounded" id="change">@lang('printcatora.task.go_with') "<span id="fault-type-new" data-fid=""></span>"</button>
                                </p>
                                <div class="buttons">
                                    @foreach($fault->faulttypes as $fault_type)
                                    <button class="btn btn-sm btn-fault-option btn-rounded" data-toggle="tooltip" data-fid="@php( print($fault_type->id) )">@lang($fault_type->title)</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <button type="submit" name="fetch" id="fetch" class="btn btn-md btn-warning btn-rounded my-4">
            <span class="">@lang('printcatora.task.fetch_another')</span>
        </button>
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
