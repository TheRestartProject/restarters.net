@extends('layouts.app')
@section('content')

  @if (\Session::has('success'))
    <div class="container">
      <div class="alert alert-success">
        {!! \Session::get('success') !!}
      </div>
    </div>
  @endif

  @if (\Session::has('danger'))
    <div class="container">
      <div class="alert alert-danger">
        {!! \Session::get('danger') !!}
      </div>
    </div>
  @endif

  <?php
    $countries = [];
    foreach (App\Helpers\Fixometer::getAllCountries() as $code => $name) {
      $countries[] = ['code' => $code, 'name' => $name];
    }
    $roles = [];
    foreach (App\Helpers\Fixometer::allRoles() as $r) {
      $roles[] = ['id' => $r->idroles, 'name' => $r->role];
    }
    $can_edit = App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator');
  ?>

  <div class="vue-placeholder vue-placeholder-large">
    <div class="vue-placeholder-content">@lang('partials.loading')...</div>
  </div>
  <div class="vue">
    <UsersPage
      :countries="{{ json_encode($countries, JSON_INVALID_UTF8_IGNORE) }}"
      :roles="{{ json_encode($roles, JSON_INVALID_UTF8_IGNORE) }}"
      :can-edit="{{ $can_edit ? 'true' : 'false' }}"
    />
  </div>

  @include('includes/modals/create-user')
@endsection
