@extends('layouts.app')

@section('title')
  Groups
@endsection

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

      <?php
        $all_groups = $groups;
        $can_create = App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') || App\Helpers\Fixometer::hasRole(Auth::user(), 'Host');
        $show_tags = App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator');

        $user = Auth::user();
        $myid = $user ? $user->id : null;
        $api_token = NULL;

        if ($user) {
            $api_token = $user->ensureAPIToken();
        }
      ?>

      <div class="vue-placeholder vue-placeholder-large">
        <div class="vue-placeholder-content">@lang('partials.loading')...</div>
      </div>

      <div class="vue">
        <GroupsPage
          csrf="{{ csrf_token() }}"
          :all-groups="{{ json_encode($all_groups, JSON_INVALID_UTF8_IGNORE) }}"
          :your-groups="{{ json_encode($your_groups, JSON_INVALID_UTF8_IGNORE) }}"
          :nearby-groups="{{ json_encode($groups_near_you, JSON_INVALID_UTF8_IGNORE) }}"
          your-area="{{ $your_area }}"
          :can-create="{{ $can_create ? 'true' : 'false' }}"
          :user-id="{{ $myid }}"
          tab="{{ $tab }}"
          :network="{{ $network ? $network : 'null' }}"
          :networks="{{ json_encode($networks, JSON_INVALID_UTF8_IGNORE) }}"
          :all-group-tags="{{ json_encode($all_group_tags, JSON_INVALID_UTF8_IGNORE) }}"
          :show-tags="{{ $show_tags ? 'true' : 'false' }}"
          api-token="{{ $api_token }}"
        />
      </div>

      @php( $user_preferences = session('column_preferences') )

    </section>
  @endsection
