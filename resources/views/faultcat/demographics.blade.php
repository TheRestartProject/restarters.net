@extends('layouts.app', ['show_login_join_to_anons' => true])

@section('extra-css')

    @include('faultcat/shared-css')

@endsection

@section('content')

<section class="faultcat">
    <div class="container mt-1 mt-sm-4">
        <div class="row row-compressed">
            <div class="col-6">
                <h1 class="pull-left">FaultCat</h1>
            </div>
            <div class="col-6">
                <a id="btn-info-open"
                   data-toggle="modal" data-target="#faultcatInfoModal"
                   class="btn btn-info btn-sm btn-rounded p-2">
                    <svg style="width:24px;height:24px;" viewBox="0 0 24 24">
                        <title>About FaultCat</title>
                        <path fill="#fff" d="M13.5,4A1.5,1.5 0 0,0 12,5.5A1.5,1.5 0 0,0 13.5,7A1.5,1.5 0 0,0 15,5.5A1.5,1.5 0 0,0 13.5,4M13.14,8.77C11.95,8.87 8.7,11.46 8.7,11.46C8.5,11.61 8.56,11.6 8.72,11.88C8.88,12.15 8.86,12.17 9.05,12.04C9.25,11.91 9.58,11.7 10.13,11.36C12.25,10 10.47,13.14 9.56,18.43C9.2,21.05 11.56,19.7 12.17,19.3C12.77,18.91 14.38,17.8 14.54,17.69C14.76,17.54 14.6,17.42 14.43,17.17C14.31,17 14.19,17.12 14.19,17.12C13.54,17.55 12.35,18.45 12.19,17.88C12,17.31 13.22,13.4 13.89,10.71C14,10.07 14.3,8.67 13.14,8.77Z"></path>
                    </svg></a>
                <img id="faultcat" class="pull-right" src="{{ asset('/images/faultcat/099-smiling-cat-face-with-heart-eyes-64px.svg.png') }}" alt="smiling cat" width="48" height="48" />
            </div>
        </div>
        <form id="save-demographics" action="{{ action([\App\Http\Controllers\FaultcatController::class, 'storeDemographics']) }}" method="POST">
            @csrf
        <div class="row problem panel p-2 mb-4 mx-1 mx-sm-0 notification">
            <div class="col">
                <p style="font-weight:bold">I am aged...</p>
                <div style="margin-bottom:20px">
                    <div class="control">
                        <label class="radio mr-3">
                            <input type="radio" name="age" value="under50"
                                {{ old('age') == "under50" ? 'checked' : '' }}
                            >
                            under 50
                        </label>
                        <label class="radio">
                            <input type="radio" name="age" value="50orover"
                                {{ old('age') == "50orover" ? 'checked' : '' }}
                            >
                            50 or over
                        </label>
                    </div>
                    @if ($errors->has('age'))
                        <p class="text-danger">please select an age group</p>
                    @endif
                </div>
                <div>
                    <p style="font-weight:bold">...and living...</p>
                    <div class="control">
                        <label class="radio mr-3">
                            <input type="radio" name="country" value="england"
                                {{ old('country') == "england" ? 'checked' : '' }}
                            >
                            in England
                        </label>
                        <label class="radio">
                            <input type="radio" name="country" value="other"
                                {{ old('country') == "other" ? 'checked' : '' }}
                            >
                            not in England
                        </label>
                    </div>
                    @if ($errors->has('country'))
                        <p class="text-danger">please select a location</p>
                    @endif
                </div>
            </div>
                <div class="container mt-4">
                    <p class="buttons">
                        <button class="btn btn-md btn-success btn-rounded" id="save">
                            <span class="underline">S</span>ave and continue</button>
                        <a href="/faultcat" id="skip" style="text-decoration:none" class="btn btn-md btn-warning btn-rounded">
                            S<span class="underline">k</span>ip for now
                        </a>
                    </p>
                </div>
            </div>
        </form>
        <div>
            <a href="#" data-toggle="modal" data-target="#demographicsModal">Why are we asking for this information?</a>
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
