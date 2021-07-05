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
        @php( $status_class = $fault->repair_status == "Repairable" ? "repairable" : "endoflife" )
    <div class="battcat-layout battcat-layout-{{ $status_class }}">
        <div class="task-step" id="step1">
            <div class="task-step-help" id="step1-help">
                <div class="task-step-help-text">
                    <div class="number">1</div>
                    <div><strong>Read the information about the broken device.</strong></span>
                    </div>
                </div>
                <div>
                    <p style="font-size: smaller">Someone brought this broken device to a community repair event.</p>
                </div>
            </div>
            <div class="task-step-info panel" id="step1-info">
                <div class="row text-left">
                    <div class="col-12 col-md-4">
                        <span class="label">Device:</span> <span class="category">@lang($fault->product_category)</span>
                    </div>
                    @if (!empty($fault->brand && $fault->brand !== 'Unknown'))
                        <div class="col-12 col-md-4">
                            <span class="label">Brand:</span> <span class="brand">{{ $fault->brand }}</span>
                        </div>
                    @endif
                    <div class="col-12 col-md-4">
                        <span class="label">Status:</span> <span class="repair-status span-{{ $status_class }}">@lang($fault->repair_status)</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <br/>
                        <div class="label">Description of problem:</div>
                        <p class="text-left">{{ $fault->problem }}</p>
                    </div>
                    </div>
                    @if ($fault->language !== $locale )
                    <div class="row">
                        <div class="col">
                            <p>
                                <button id="btn-translate" class="btn-sm btn-outline-light">
                                    <a href="https://translate.google.com/#view=home&op=translate&sl={{ $fault->language }}&tl={{ $locale }}&text={{ $fault->problem }}" target="_blank">
                                        @lang('battcatora.task.translate')
                                    </a>
                                </button>
                            </p>
                        </div>
                    </div>
                    @endif
            </div>
        </div>
        <div class="task-step" id="step2">
            <div class="task-step-help" id="step2-help">
                <div class="task-step-help-text">
                    <div class="number">2</div>
                    <div>
                        <strong>
                            @if ($fault->repair_status == "Repairable")
                                @lang('battcatora.task.question-repairable')
                            @else
                                @lang('battcatora.task.question-endoflife')
                            @endif
                        </strong>
                    </div>
                </div>
                <div>
                <p style="font-size: smaller">Select the option that best fits the problem described above.</p>
                </div>
            </div>
            <div id="step2-info" class="panel text-center">
                <form id="log-task" action="" method="POST">
                @csrf
                    <div class="fault-type">
                        <div class="options">
                                    <input type="hidden" id="id-ords" name="id-ords" value="{{ $fault->id_ords }}">
                                    <input type="hidden" id="fault-type-id" name="fault-type-id" value="">
                                    <p class="confirm hide">
                                        <button class="btn-md btn-primary" id="change">@lang('battcatora.task.go_with') "<span id="fault-type-new" data-fid=""></span>"</button>
                                    </p>
                                    <div class="options mb-3">
                                        <div class="buttons">
                                            @foreach($fault->faulttypes as $fault_type)
                                                <button class="btn btn-md btn-fault-option btn-rounded" data-fname="{{ $fault_type->title }}" data-toggle="tooltip" data-fid="{{ $fault_type->id }}">@lang($fault_type->title)</button>
                                            @endforeach
                                        </div>
                                    </div>
                        </div>
                    </div>
                </form>
                <div>
                <button type="submit" name="fetch" id="fetch" class="btn btn-primary">
                    <span class="">@lang('battcatora.task.fetch_another')</span>
                </button>
                </div>
            </div>
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
