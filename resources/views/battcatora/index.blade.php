@extends('layouts.app', ['show_navbar_to_anons' => true, 'show_login_join_to_anons' => true, 'hide_language' => false])

@section('extra-css')

@include('battcatora/shared-css')

@endsection

@section('extra-meta')
<meta name="description" content="Help analyse faults caused by batteries in devices brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta name="keywords" content="BattCat, batteries, community events, Restart Parties, Repair Cafés, repair data, Right to Repair, Open Repair Alliance, The Restart Project, Open Repair Data">
<meta property="og:title" content="BattCat">
<meta property="og:description" content="Help analyse faults caused by batteries in devices brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta property="og:image" content="{{ asset('/images/battcatora/og-battcat-toolbox-new.png') }}">
<meta property="og:url" content="https://restarters.net/battcat/">
<meta property="og:type" content="website">
@endsection

@section('title')
{{ $title }}
@endsection

@section('content')

<section class="battcat">
    <div class="container mt-1 mt-sm-2">
        <div class="row row-compressed align-items-center">
            <div class="col-5">
                <h1>BattCat</h1>
            </div>
            <div class="col-7 text-right">
                <a id="btn-info-open" data-toggle="modal" data-target="#battcatoraInfoModal" class="btn btn-primary ml-2">
                    @lang('battcatora.about')
                </a>
                <a class="btn btn-primary " href="{{ '/battcat/status' }}">
                    @lang('battcatora.status.status')
                </a>
            </div>
        </div>
        @if (!$signpost)
        <!-- <div class="row row-compressed align-items-left">
            <div class="col-12 text-left strapline">
                <p>@lang('battcatora.task.strapline')
                    <a href="javascript:void(0);" id="a-info-open" data-toggle="modal" data-target="#battcatoraInfoModal">@lang('battcatora.task.learn_more')</a>
                </p>
            </div>
        </div> -->
        @elseif ($signpost < 5) <div class="row row-compressed information-alert banner alert-secondary align-items-left signpost">
            <div class="col-1 text-left">
                <img class="d-none d-sm-block" src="{{ asset('/images/battcatora/signpost.png') }}" alt="i" />
            </div>
            <div class="col-11 text-left">
                <h5>@lang('battcatora.task.did_you_know')</h5>
                <p>@lang('battcatora.task.signpost_' . $signpost)</p>
            </div>
    </div>
    @else
    <div class="row row-compressed align-items-left">
        <div class="col-12 text-center">
            <p>@lang('battcatora.task.signpost_' . $signpost)</p>
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
    <div class="row row-compressed align-items-left">
        <h2 class="col-12 text-left">@lang('battcatora.task.subtitle')</h2>
    </div>
    <div class="row p-3 mb-4 mx-1 mx-sm-0">
        <div class="col-12">
            <div class="row device">
                <div class="device-meta col-4">
                    <ul>
                        <li><span class="source">@lang('battcatora.task.source'): {{ $fault->partner }}</span></li>
                        <li><span class="category">@lang($fault->product_category)</span></li>
                        @if (!empty($fault->brand && $fault->brand !== 'Unknown'))
                        <li><span class="brand">{{ $fault->brand }}</span></li>
                        @endif
                        @if ($fault->repair_status == "Repairable")
                        <li><span class="repair-status span-repairable">@lang($fault->repair_status)</span></li>
                        @else
                        <li><span class="repair-status span-endoflife">@lang($fault->repair_status)</span></li>
                        @endif
                    </ul>
                </div>
                @if ($fault->language == $locale )
                <div class="device-problem col-8">
                    <p class="text-center">{{ $fault->problem }}</p>
                </div>
                @else
                <div class="device-problem col-7">
                    <p class="text-center">{{ $fault->problem }}</p>
                </div>
                <div class="device-problem col-1">
                    <p>
                        <button id="btn-translate" class="btn-sm btn-outline-light">
                            <a href="https://translate.google.com/#view=home&op=translate&sl={{ $fault->language }}&tl={{ $locale }}&text={{ $fault->problem }}" target="_blank">
                                @lang('battcatora.task.translate')
                            </a>
                        </button>
                    </p>
                </div>
                @endif
            </div>
            <form id="log-task" action="" method="POST">
                @csrf
                <div class="container fault-type">
                    <div class="row options">
                        <div class="col p-3">
                            @if ($fault->repair_status == "Repairable")
                            <p><span class="question">@lang('battcatora.task.question-repairable')</span></p>
                            @else
                            <p><span class="question">@lang('battcatora.task.question-endoflife')</span></p>
                            @endif
                            <div class="container">
                                <input type="hidden" id="id-ords" name="id-ords" value="{{ $fault->id_ords }}">
                                <input type="hidden" id="fault-type-id" name="fault-type-id" value="">
                                <p class="confirm hide">
                                    <button class="btn-md btn-info btn-rounded" id="change">@lang('battcatora.task.go_with') "<span id="fault-type-new" data-fid=""></span>"</button>
                                </p>
                                <div class="container options mb-3">
                                    <div class="buttons">
                                        @foreach($fault->faulttypes as $fault_type)
                                        @if ($fault_type->title !== "Poor data")
                                        <button class="btn btn-sm btn-fault-option btn-rounded" data-toggle="tooltip" data-fid="{{ $fault_type->id }}">@lang($fault_type->title)</button>
                                        @else
                                        <button class="btn btn-sm btn-fault-option btn-fault-poordata btn-rounded" data-toggle="tooltip" data-fid="{{ $fault_type->id }}">@lang($fault_type->title)</button>
                                        @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <button type="submit" name="fetch" id="fetch" class="btn btn-md btn-warning btn-rounded my-4">
                <span class="">@lang('battcatora.task.fetch_another')</span>
            </button>
        </div>
    </div>
    @endif
    <div id="ora-partnership" class="mt-8 mb-4">
        <hr />
        <p class="mb-1">@lang('battcatora.branding.powered_by')</p>
        <a href="https://openrepair.org" target="_blank">
            <img src="{{ asset('images/battcatora/ora-logo.png') }}" alt="Open Repair Alliance logo" />
        </a>
    </div>
    @include('battcatora/info-modal')
    @include('battcatora/task-survey-modal')
    @include('partials/task-cta-ora-modal')
</section>

@endsection

@section('scripts')
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {

        [...document.querySelectorAll('.btn-fault-option')].forEach(elem => {
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
            [...document.querySelectorAll('.btn-fault-option')].forEach(elem => {
                elem.classList.remove('btn-fault-selected');

            });
            document.querySelector('.btn-fault-poordata').classList.remove('btn-fault-selected');
            e.target.classList.add('btn-fault-selected');
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