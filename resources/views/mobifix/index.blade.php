@extends('layouts.app', ['show_login_join_to_anons' => true, 'hide_language' => true])

@section('extra-css')

@include('mobifix/shared-css')

@endsection

@section('content')

<section class="mobifix">
    <div class="container mt-1">
        <a id="btn-cta-open"data-toggle="modal" data-target="#taskctaModal"class="hide">cta</a>
        <div class="row row-compressed">
            <div class="col-6">
                <h1 class="pull-left">MobiFix</h1>
            </div>
            <div class="col-6">
                <?php
                $img = 'whale-spouting.png';
                $alt = 'happy whale';
                if ($fault) {
                    switch ($fault->repair_status) {
                        case 'End of life':
                        case 'Unknown':
                            $img = 'whale.png';
                            $alt = 'sad whale';
                            break;
                    }
                }
                ?>
                <a id="btn-info-open"
                   data-toggle="modal" data-target="#mobifixInfoModal"
                   class="btn btn-info btn-sm btn-rounded p-2">
                    <svg style="width:24px;height:24px;" viewBox="0 0 24 24">
                    <title>About MobiFix</title>
                    <path fill="#fff" d="M13.5,4A1.5,1.5 0 0,0 12,5.5A1.5,1.5 0 0,0 13.5,7A1.5,1.5 0 0,0 15,5.5A1.5,1.5 0 0,0 13.5,4M13.14,8.77C11.95,8.87 8.7,11.46 8.7,11.46C8.5,11.61 8.56,11.6 8.72,11.88C8.88,12.15 8.86,12.17 9.05,12.04C9.25,11.91 9.58,11.7 10.13,11.36C12.25,10 10.47,13.14 9.56,18.43C9.2,21.05 11.56,19.7 12.17,19.3C12.77,18.91 14.38,17.8 14.54,17.69C14.76,17.54 14.6,17.42 14.43,17.17C14.31,17 14.19,17.12 14.19,17.12C13.54,17.55 12.35,18.45 12.19,17.88C12,17.31 13.22,13.4 13.89,10.71C14,10.07 14.3,8.67 13.14,8.77Z"></path>
                    </svg></a>
                <a href="/mobifix/status/">
                    <img id="mobifix" class="pull-right" src="{{ asset('/images/mobifix/'.$img) }}" alt="{{ $alt }}" width="48" height="48" />
                </a>
            </div>
        </div>

        @if ($fault)
        <div class="row problem panel p-3 mb-4 mx-1 mx-sm-0 notification">
            <div class="col">
                <div class="row">
                    <div class="col">
                        <p>
                            @if (!empty($fault->brand) && $fault->brand !== 'Unknown')
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info">@php( print($fault->brand))</span>
                            @endif
                            @if (!empty($fault->model) && $fault->model !== 'Unknown')
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info">@php( print($fault->model))</span>
                            @endif
                            @if ($fault->repair_status !== 'Unknown')
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info">@php( print($fault->repair_status))</span>
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
                            <a href="https://translate.google.com/#view=home&op=translate&sl=auto&tl=en&text=@php( print($fault->translate))" target="_blank">
                                Translate
                            </a>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <form id="get-task" action="" method="GET"></form>
        <form id="log-task" action="" method="POST">
            @csrf
            <div class="container fault-type">
                <div class="row">
                    <div class="col panel p-3">
                        <p><span class="question">Where is the main fault?</span></p>
                        <div class="container">
                            <input type="hidden" id="iddevices" name="iddevices" value="@php( print($fault->iddevices))">
                            <input type="hidden" id="fault_type" name="fault_type" value="">
                            @if (count($fault->suggestions))
                            <div class="buttons suggestions ">
                                <p class="title is-size-6-mobile is-size-6-tablet">Suggestions</p>
                                <p>
                                    @foreach($fault->suggestions as $fault_type)
                                    <button class="btn btn-sm btn-fault-suggestion btn-success btn-rounded" data-toggle="tooltip" title="@php( print($fault->descriptions[$fault_type]) )"><span>@php( print($fault_type))</span></button>
                                    @endforeach
                                </p>
                            </div>
                            @endif
                            <div class="container options mb-3">
                                <p class="confirm hide">
                                    <button class="btn-md btn-info btn-rounded" id="change"><span class="underline">G</span>o with "<span id="fault-type-new"></span>"</button>
                                </p>
                                <div class="buttons">
                                    @foreach($fault->faulttypes as $fault_type)
                                    <button class="btn btn-sm btn-fault-option btn-rounded" data-toggle="tooltip" title="@php( print($fault->descriptions[$fault_type]))"><span>@php( print($fault_type))</span></button>
                                    @endforeach
                                </div>
                            </div>
                            <button type="submit" name="fetch" id="fetch" class="btn btn-md btn-warning btn-rounded">
                                <span class="">I don't know,&nbsp;<span class="underline">F</span>etch another repair</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @endif
    </div>
    @include('mobifix/info-modal')
    @include('partials/task-cta-modal')
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
            document.forms['get-task'].submit();
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
            document.querySelector('.confirm').classList.replace('hide', 'show');
            document.querySelector('#fault-type-new').innerText = e.target.innerText;
            document.querySelector('#change').focus({
                preventScroll: false
            });
        }

        function doChange(e) {
            console.log(document.querySelector('#fault-type-new').innerText);
            if (document.querySelector('#fault-type-new').innerText !== '') {
                document.querySelector('#fault_type').value = document.querySelector('#fault-type-new').innerText;
                submitForm();
            }
        }

        function submitForm() {
            console.log('submitForm - iddevices: ' +
                    document.querySelector('#iddevices').value +
                    ' / fault_type: ' +
                    document.querySelector('#fault_type').value);
            document.forms['log-task'].submit();
        }

        if (window.location.href.indexOf('cta') != -1) {
            document.getElementById('btn-cta-open').click();
        }

    }, false);</script>

@endsection
