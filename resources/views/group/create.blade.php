@extends('layouts.app')
@section('content')
<section class="groups">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-12">
        <div class="vue">
          <b-card no-body class="box mt-4">
            <b-card-body class="p-4">
              <GroupAddEditPage box />
            </b-card-body>
          </b-card>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
@section('scripts')
  @include('includes/gmap')
@endsection