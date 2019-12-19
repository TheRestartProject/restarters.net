@extends('layouts.app')

@section('extra-css')

    <style>
     body {
         text-align: center !important;
     }

     h1 {
         font-family: 'Asap' !important;
         font-weight: bold !important;
         float: left; /* bootstrap pull-left class working locally but not on server!  falling back on this. */
     }

     img#faultcat {
         width: 48px;
         height: 48px;
         float: right; /* bootstrap pull-right class working locally but not on server!  falling back on this. */
     }


     .title {
         font-weight: bold;
     }

     .is-horizontal-center {
         justify-content: center;
     }

     .hide {
         display: none;
     }

     .show {
         display: block;
     }

     .underline {
         text-decoration: underline;
     }

     .options {
         margin-top: 15px;
     }

     .problem {
         font-size: 1rem;
         background-color: #f5f5f5;
         border: 5px solid #FFDD57;
         border-radius: 5px;
     }

     .tag {
         margin-bottom: 2px;
     }

     .faultcat .btn {
         font-family: 'Open Sans';
     }

     .btn-fault-option-current {
         background-color: #bdbdbd !important;
     }

     #btn-translate a {
         color: white;
         text-decoration: underline;
     }

     #Y,
     #N,
     #fetch,
     #change {
         margin-bottom: 2px;
     }

    </style>

@endsection

@section('content')

<section class="faultcat">
    <div class="container mt-1 mt-sm-4">
        <div class="row row-compressed">
            <div class="col-6">
                <h1 class="pull-left">FaultCat</h1>
            </div>
            <div class="col-6">
                <img id="faultcat" class="pull-right" src="{{ asset('/images/faultcat/099-smiling-cat-face-with-heart-eyes-64px.svg.png') }}" alt="smiling cat" width="48" height="48" />
            </div>
        </div>
        <div class="row problem p-2 mb-2 mx-1 mx-sm-0 notification">
            <div class="col">
                    <p class="is-size-6-mobile is-size-6-tablet">I am aged...</p>
                    <div class="field is-grouped is-grouped-centered is-grouped-multiline">
                        <div class="control">
                            <label class="radio mr-3">
                                <input type="radio" name="age" value="under50">
                                under 50
                            </label>
                            <label class="radio">
                                <input type="radio" name="age" value="over50">
                                over 50
                            </label>
                        </div>
                        <br/>
                        <p>...and living...</p>
                        <div class="control">
                            <label class="radio mr-3">
                                <input type="radio" name="country" value="EN">
                                in England
                            </label>
                            <label class="radio">
                                <input type="radio" name="country" value="other">
                                not in England
                            </label>
                        </div>
                    </div>
                </div>
        </div>
        <form id="log-task" action="faultcat" method="POST">
            @csrf
            <div class="container fault-type">
                <div class="container">
                    <p class="buttons">
                        <button class="btn btn-md btn-success btn-rounded" id="save">
                            <span class="underline">S</span>ave and continue</button>
                        <a href="/faultcat" id="skip" class="btn btn-md btn-warning btn-rounded">
                            S<span class="underline">k</span>ip for now
                        </a>
                    </p>
                </div>
            </div>
        </form>
        <div>
            <a href="#" data-toggle="modal" data-target="#demographicsModal">We are we asking for this information?</a>
        </div>

        @include('faultcat/info-modal')
        @include('faultcat/demographics-modal')
</section>

@endsection

@section('scripts')
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {

        document.addEventListener("keypress", function(e) {
            if (e.code == 'KeyS') {
                e.preventDefault();
                document.getElementById('save').click();
            } else if (e.code == 'KeyK') {
                e.preventDefault();
                document.getElementById('skip').click();
            }
        }, false);
    }, false);
</script>
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {

        /*document.querySelector('.dropdown-trigger').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('user').classList.toggle('is-active');
        });

        document.getElementById('user').addEventListener('mouseleave', e => {
            document.getElementById('user').classList.remove('is-active');
        });*/

        /*document.getElementById('btn-info-open').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('modal-info').classList.add('is-active');
        }, false);*/

        /*document.getElementById('btn-info-close').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('modal-info').classList.remove('is-active');
        }, false);*/

        /*document.addEventListener("keypress", function(e) {
            if (e.code == 'KeyI') {
                e.preventDefault();
                document.getElementById('btn-info-open').click();
            } else if (e.code == 'KeyU') {
                e.preventDefault();
                document.querySelector('.dropdown-trigger').click();
            }
        }, false);*/

    }, false);
</script>

@endsection
