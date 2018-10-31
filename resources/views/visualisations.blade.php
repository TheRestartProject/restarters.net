@extends('layouts.app')

@section('content')

<section style="background:#F7F5ED">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 d-flex flex-column">
                @include('partials.visualisations.event')
            </div>
            <div class="col-lg-6 d-flex flex-column">
                @include('partials.visualisations.group')
            </div>
        </div>
    </div>
</section>
@endsection