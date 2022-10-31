@extends('layouts.app')
@section('content')
  <section class="groups">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-12">
          <div class="vue">
            <GroupAddEditPage :idgroups="{{ $id }}" />
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
@section('scripts')
  @include('includes/gmap')
@endsection