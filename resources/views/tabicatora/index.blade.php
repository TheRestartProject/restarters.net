@extends('layouts.app', ['show_navbar_to_anons' => true, 'show_login_join_to_anons' => true, 'hide_language' => false])

@section('extra-css')

@include('tabicatora/shared-css')

@endsection

@section('extra-meta')
<meta name="description" content="Help analyse faults in smartphones brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta name="keywords" content="TabiCat, tablets, iPad, Kindle, Samsung, Amazon Fire, e-reader, satnav, TomTom, community events, Restart Parties, Repair Cafés, repair data, Right to Repair, Open Repair Alliance, The Restart Project, Open Repair Data">
<meta property="og:title" content="TabiCat">
<meta property="og:description" content="Help analyse faults in tablets and e-readers brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta property="og:image" content="{{ asset('/images/tabicatora/og-tabicat-toolbox-new.png') }}">
<meta property="og:url" content="https://restarters.net/tabicat/">
<meta property="og:type" content="website">
@endsection

@section('title')
{{ $title }}
@endsection

@section('content')

<section class="tabicat">
    <div class="container mt-1 mt-sm-2">
        <div class="row row-compressed align-items-center">
            <div class="col-5">
                <h1>TabiCat
                    <img id="tabicat" class="pull-left d-none d-sm-block" src="{{ asset('/images/tabicatora/paw-prints.png') }}" alt="TabiCat status" />
                </h1>
            </div>
            <div class="col-7 text-right">
                <a id="btn-info-open" data-toggle="modal" data-target="#tabicatoraInfoModal" class="btn btn-primary ml-2">
                    @lang('tabicatora.about')
                </a>
                <a class="btn btn-primary " href="{{ '/tabicat/status' }}">
                    @lang('tabicatora.status.status')
                </a>
            </div>
        </div>
        @if (!$signpost)
        <div class="row row-compressed align-items-left">
            <div class="col-12 text-left strapline">
                <p>@lang('tabicatora.task.strapline')
                    <a href="javascript:void(0);" id="a-info-open" data-toggle="modal" data-target="#tabicatoraInfoModal">@lang('tabicatora.task.learn_more')</a>
                </p>
            </div>
        </div>
        @elseif ($signpost < 5) <div class="row row-compressed information-alert banner alert-secondary align-items-left signpost">
            <div class="col-1 text-left">
                <img class="d-none d-sm-block" src="{{ asset('/images/tabicatora/signpost.png') }}" alt="i" />
            </div>
            <div class="col-11 text-left">
                <h5>@lang('tabicatora.task.did_you_know')</h5>
                <p>@lang('tabicatora.task.signpost_' . $signpost)</p>
            </div>
    </div>
    @else
    <div class="row row-compressed align-items-left">
        <div class="col-12 text-center">
            <p>@lang('tabicatora.task.signpost_' . $signpost)</p>
        </div>
    </div>
    @endif

    <a id="btn-cta-open" data-toggle="modal" data-target="#taskctaModal" class="hide">cta</a>
    <a id="btn-survey-open" data-toggle="modal" data-target="#tasksurveyModal" class="hide">survey</a>

    @if($errors->any())
    <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 text-center">
        {{$errors->first()}}
    </div>
    @endif

    @if ($fault)
    <div class="row problem panel p-3 mb-4 mx-1 mx-sm-0 notification">
        <div class="col">
            <div class="row">
                <div class="col">
                    <p>
                        <span class="btn btn-md py-1 py-sm-2 btn-fault-info">@lang('tabicatora.task.source'): {{ $fault->partner }}</span>
                        @if (!empty($fault->brand && $fault->brand !== 'Unknown'))
                        <span class="btn btn-md py-1 py-sm-2 btn-fault-info">{{ $fault->brand }}</span>
                        @endif
                        @if ($fault->repair_status !== 'Unknown')
                        <span class="btn btn-md py-1 py-sm-2 btn-fault-info">@lang($fault->repair_status)</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="row">
                @if ($fault->language == $locale )
                <div class="col-12">
                    <p class="subtitle">{{ $fault->problem }}</p>
                </div>
                @else
                <div class="col-8 offset-sm-2">
                    <p class="subtitle">{{ $fault->problem }}</p>
                </div>
                <div class="col-4 col-sm-2">
                    <button id="btn-translate" class="pull-right btn btn-md btn-dark px-3 py-1">
                        <a href="https://translate.google.com/#view=home&op=translate&sl={{ $fault->language }}&tl={{ $locale }}&text={{ $fault->translate }}" target="_blank">
                            @lang('tabicatora.task.translate')
                        </a>
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    <form id="log-task" action="" method="POST">
        @csrf
        <div class="container fault-type">
            <div class="row">
                <div class="col panel p-3">
                    <p><span class="question">@lang('tabicatora.task.where_is_the_main_fault')?</span></p>
                    <div class="container">
                        <input type="hidden" id="id-ords" name="id-ords" value="{{ $fault->id_ords }}">
                        <input type="hidden" id="fault-type-id" name="fault-type-id" value="">
                        <p class="confirm hide">
                            <button class="btn-md btn-info btn-rounded" id="change">@lang('tabicatora.task.go_with') "<span id="fault-type-new" data-fid=""></span>"</button>
                        </p>
                        @if (count($fault->suggestions))
                        <div class="buttons suggestions">
                            <p class="title is-size-6-mobile is-size-6-tablet">@lang('tabicatora.task.suggestions')</p>
                            <p>
                                @foreach($fault->suggestions as $fault_type)
                                <button class="btn btn-sm btn-fault-suggestion btn-success btn-rounded" data-toggle="tooltip" data-fid="{{ $fault_type->id }}">@lang($fault_type->title)</button>
                                @endforeach
                            </p>
                        </div>
                        @endif
                        <div class="container options mb-3">
                            <div class="buttons">
                                @foreach($fault->faulttypes as $fault_type)
                                <button class="btn btn-sm btn-fault-option btn-rounded" data-toggle="tooltip" data-fid="{{ $fault_type->id }}">@lang($fault_type->title)</button>
                                @endforeach
                                <button id="btn-poordata" class="btn btn-sm btn-fault-option btn-rounded" data-toggle="tooltip" data-fid="{{ $poor_data[0]->id }}">@lang($poor_data[0]->title)</button>
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
    <div id="ora-partnership" class="mt-8 mb-4">
        <hr />
        <p class="mb-1">@lang('tabicatora.branding.powered_by')</p>
        <a href="https://openrepair.org" target="_blank">
            <img src="{{ asset('images/tabicatora/ora-logo.png') }}" alt="Open Repair Alliance logo" />
        </a>
    </div>
    @include('tabicatora/info-modal')
    @include('tabicatora/task-survey-modal')
    @include('partials/task-cta-ora-modal')
</section>

@endsection

@section('scripts')
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {

        [...document.querySelectorAll('.btn-fault-option, .btn-fault-suggestion')].forEach(elem => {
            elem.addEventListener('click', function(e) {
                e.preventDefault();
                doOption(e);
            });
        });
        document.getElementById('change').addEventListener('click', function(e) {
            e.preventDefault();
            doChange();
        }, false);
        document.getElementById('fetch').addEventListener('click', function(e) {
            e.preventDefault();
            fetchNew();
        }, false);
        document.addEventListener("keypress", function(e) {
            if (e.code == 'KeyF') {
                e.preventDefault();
                document.getElementById('fetch').click();
            } else if (e.code == 'KeyG') {
                e.preventDefault();
                doChange();
            } else if (e.code == 'KeyI') {
                e.preventDefault();
                document.getElementById('btn-info-open').click();
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

        if (window.location.href.indexOf('survey') != -1) {
            document.getElementById('btn-survey-open').click();
        }

    }, false);
</script>

@endsection
