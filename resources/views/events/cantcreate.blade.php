@extends('layouts.app')
@section('content')
  <div class="vue-placeholder vue-placeholder-large">
    <div class="vue-placeholder-content">@lang('partials.loading')...</div>
  </div>
  <div class="vue">
    <CantCreateEventPage />
  </div>
@endsection
