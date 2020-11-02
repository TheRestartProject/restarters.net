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

      @if ($all)
        <form action="/group/all/search" method="get" id="device-search">
          <div class="row justify-content-center">
            <div class="col-lg-3">
              @include('group.sections.sidebar-all-groups')
            </div>
            <div class="col-lg-9">
              @include('group.sections.all-groups')
            </div>
          </div>
        </form>
      @else
        <?php
          if (!is_null($your_groups)) {
            foreach ($your_groups as &$group) {
              $group_image = $group->groupImage;
              if (is_object($group_image) && is_object($group_image->image)) {
                $group_image->image->path;
              }

              $group['location'] = $group->getLocation();
              $group['next_event'] = $group->getNextUpcomingEvent();
            }
          }

          if (!is_null($groups_near_you)) {
            foreach ($groups_near_you as &$group) {
              $group_image = $group->groupImage;
              if (is_object($group_image) && is_object($group_image->image)) {
                $group_image->image->path;
              }

              $group['location'] = $group->getLocation();
              $group['nextevent'] = $group->getNextUpcomingEvent();
            }
          }

          $can_create = FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'Host');
          $myid = Auth::user() ? Auth::user()->id : null
        ?>

        <div class="vue">
            <GroupsPage
                :your-groups="{{ json_encode($your_groups) }}"
                your-area="{{ $your_area }}"
                :nearby-groups="{{ json_encode($groups_near_you) }}"
                :can-create="{{ $can_create ? 'true' : 'false' }}"
                :user-id="{{ $myid }}"
            />
        </div>

        @php( $user_preferences = session('column_preferences') )
      @endif

    </section>
  @endsection
