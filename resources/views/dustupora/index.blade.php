@extends('layouts.app', ['show_navbar_to_anons' => true, 'show_login_join_to_anons' => true, 'hide_language' => false])

@section('extra-css')

@include('dustupora/shared-css')

@endsection

@section('extra-meta')
<meta name="description" content="Help analyse faults in vacuum cleaners brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta name="keywords" content="DustUp, vacuum, Hoover, Dyson, community events, Restart Parties, Repair Cafés, repair data, Right to Repair, Open Repair Alliance, The Restart Project, Open Repair Data">
<meta property="og:title" content="DustUp">
<meta property="og:description" content="Help analyse faults caused in vacuum cleaners brought to events such as Repair Cafés and Restart Parties, and contribute to the current push for Right to Repair!">
<meta property="og:image" content="{{ asset('/images/dustupora/og-dustup-logo.png') }}">
<meta property="og:url" content="https://restarters.net/dustup/">
<meta property="og:type" content="website">
@endsection

@section('title')
{{ $title }}
@endsection

@section('content')

<section class="battcat">
    <div class="container mt-1 mt-sm-2">
        <div class="row row-compressed align-items-center">
            <div class="col-12 col-md-9 order-12 order-md-1 mt-2 mt-md-0">
                <h1>@lang('dustupora.task.title')</h1>
            </div>
            <div class="col-12 col-md-3 order-1 order-md-12 text-right">
                <a id="btn-info-open" data-toggle="modal" data-target="#dustuporaInfoModal" class="btn btn-primary ml-2">
                    @lang('dustupora.about')
                </a>
                <a class="btn btn-primary " href="{{ '/dustup/status' }}">
                    @lang('dustupora.status.status')
                </a>
            </div>
        </div>
        @if (!$signpost)
        <div class="row row-compressed align-items-left">
            <div class="col-12 text-left strapline">
                <p>@lang('dustupora.task.strapline')
                    <a href="javascript:void(0);" id="a-info-open" data-toggle="modal" data-target="#dustuporaInfoModal">@lang('dustupora.task.learn_more')</a>
                </p>
            </div>
        </div>
        @elseif ($signpost < $max_signposts)
        <div class="row row-compressed information-alert banner alert-secondary align-items-left signpost">
            <div class="col-1 text-left">
                <img class="d-none d-sm-block" src="{{ asset('/images/dustupora/signpost.png') }}" alt="i" />
            </div>
            <div class="col-11 text-left">
                <h5>@lang('dustupora.task.did_you_know')</h5>
                <p>@lang('dustupora.task.signpost_' . $signpost)</p>
            </div>
        </div>
        @else
        <div class="row row-compressed align-items-left">
            <div class="col-12 text-center">
                <p>@lang('dustupora.task.signpost_' . $signpost)</p>
            </div>
        </div>
        @endif

    <a id="btn-cta-open" data-toggle="modal" data-target="#taskctaModal" class="hide">cta</a>

    @if($errors->any())
    <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 text-center">
        {{$errors->first()}}
    </div>
    @endif

    @if ($fault)
    @php( $status_class = "repairable" )
    <div class="battcat-layout battcat-layout-{{ $status_class }}">
        <div class="task-step" id="step1">
            <div class="task-step-help" id="step1-help">
                <div class="task-step-help-text">
                    <div class="number">1</div>
                    <div><strong>@lang('dustupora.task.step1')</strong></span>
                    </div>
                </div>
                <div>
                    <p>@lang('dustupora.task.step1-extra')</p>
                </div>
            </div>
            <div class="task-step-info panel" id="step1-info">
                <div class="row text-left">
                    <div class="col-12 col-md-4">
                        <span class="label">@lang('dustupora.task.status'):</span> <span class="repair-status span-{{ $status_class }}">@lang($fault->repair_status)</span>
                    </div>
                    @if (!empty($fault->brand) && $fault->brand !== 'Unknown')
                    <div class="col-12 col-md-4">
                        <span class="label">@lang('dustupora.task.brand'):</span> <span class="brand">{{ $fault->brand }}</span>
                    </div>
                    @endif
                </div>
                <div class="row">
                    <div class="col">
                        <br />
                        <div class="label">@lang('dustupora.task.problem'):</div>
                        <p class="text-left">{{ $fault->problem }}</p>
                    </div>
                </div>
                @if ($fault->language !== $locale )
                <div class="row">
                    <div class="col">
                        <br />
                        <div class="label">@lang('dustupora.task.translation'):</div>
                        <p>{{ $fault->translate }}
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
                            <p>@lang('dustupora.task.step2')</p>
                        </strong>
                    </div>
                    <div>
                        <p>@lang('dustupora.task.step2-extra')</p>
                    </div>
                </div>
            </div>
            <div id="step2-info" class="panel text-center">
                <form id="log-task" action="" method="POST">
                    @csrf
                    <div class="fault-type">
                        <div class="options">
                            <input type="hidden" id="id-ords" name="id-ords" value="{{ $fault->id_ords }}">
                            <input type="hidden" id="fault-type-id" name="fault-type-id" value="">
                            @if (count($fault->suggestions))
                            <div class="buttons suggestions">
                                <p class="title is-size-6-mobile is-size-6-tablet">@lang('dustupora.task.suggestions')</p>
                                <p>
                                    @foreach($fault->suggestions as $fault_type)
                                    <button class="btn btn-sm btn-fault-suggestion btn-rounded" data-toggle="tooltip" data-fid="@php( print($fault_type->id) )">@lang($fault_type->title)</button>
                                    @endforeach
                                </p>
                            </div>
                            @endif
                            <p class="confirm hide">
                                <button class="btn-md btn-primary" id="change">@lang('dustupora.task.go_with') "<span id="fault-type-new" data-fid=""></span>" &rarr;</button>
                            </p>
                            <div class="options mb-3">
                                <div class="buttons">
                                @foreach($fault->faulttypes as $fault_type)
                                <button class="btn btn-sm btn-fault-option btn-rounded" data-toggle="tooltip" data-fid="{{ $fault_type->id }}">@lang($fault_type->title)</button>
                                @endforeach
                                <button id="btn-poordata" class="btn btn-sm btn-fault-option btn-rounded" data-toggle="tooltip" data-fid="{{ $poor_data[0]->id }}">@lang($poor_data[0]->title)</button>
                            </div>
                            </div>
                        </div>
                    </div>
                </form>
                <div>
                    <button type="submit" name="fetch" id="fetch" class="btn btn-primary">
                        <span class="">@lang('dustupora.task.fetch_another') &rarr;</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
    <div id="progress" class="mt-8 mb-4">
        <strong>@lang('dustupora.task.progress_title')</strong>
        <br>@lang('dustupora.task.progress_subtitle')
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width:{{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">{{ $progress }}%</div>
        </div>
    </div>
    <hr>
    <div id="ora-partnership" class="mt-8 mb-4">
        <p class="mb-1">@lang('dustupora.branding.powered_by')</p>
        <a href="https://openrepair.org" target="_blank">
            <img src="{{ asset('images/dustupora/ora-logo.png') }}" alt="Open Repair Alliance logo" />
        </a>
    </div>
    @include('dustupora/info-modal')
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
            } else if (e.code == 'KeyC') {
                e.preventDefault();
                document.getElementById('btn-cta-open').click();
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

    }, false);
</script>

@endsection