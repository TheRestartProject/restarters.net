@extends('layouts.app')
@section('content')
<section class="groups">
  <div class="container">

    @if (\Session::has('success'))
        <div class="alert alert-success">
            {!! \Session::get('success') !!}
        </div>
    @endif
    @if (\Session::has('warning'))
        <div class="alert alert-warning">
            {!! \Session::get('warning') !!}
        </div>
    @endif
      @if (\Session::has('danger'))
        <div class="alert alert-danger">
          {!! \Session::get('danger') !!}
        </div>
      @endif

      <div class="vue">
      <EventAddEditPage
          csrf="{{ csrf_token() }}"
          :duplicate-from="<?php echo isset($duplicateFrom) ? e(json_encode($duplicateFrom, JSON_INVALID_UTF8_IGNORE)) : 'null'; ?>"
          @if( App\Helpers\Fixometer::hasRole($user, 'Administrator') )
          :groups="{{ json_encode($allGroups, JSON_INVALID_UTF8_IGNORE) }}"
          @else
          :groups="{{ json_encode($user_groups, JSON_INVALID_UTF8_IGNORE) }}"
          @endif
          :can-approve="{{ (App\Helpers\Fixometer::hasRole( Auth::user(), 'Administrator') || Auth::user()->isCoordinatorForGroup(Group::find($selected_group_id))) ? "true" : "false" }}"
      />
    </div>

  </div>
</section>
@endsection

@section('scripts')
@include('includes/gmap')
@endsection