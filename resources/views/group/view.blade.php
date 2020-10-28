@extends('layouts.app')
@section('title')
    {{ $group->name }}
@endsection
@section('content')
<section class="events group-view">
  <div class="container">

      <?php if( isset($_GET['message']) && $_GET['message'] == 'invite' ): ?>
        <div class="alert alert-info" role="alert">
          Thank you, your invitation has been sent
        </div>
      <?php endif; ?>

      @if(session()->has('response'))
        @php( FixometerHelper::printResponse(session('response')) )
      @endif

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

      @if ($has_pending_invite)
          <div class="alert alert-success">
              You have an invitation to this group.  Please click 'Join Group' if you would like to join.
          </div>
      @endif

      <?php
          // Trigger expansion of group.
          $group_image = $group->groupImage;
          if (is_object($group_image) && is_object($group_image->image)) {
              $group_image->image->path;
          }

          $can_edit_group = FixometerHelper::hasRole( $user, 'Administrator') || $isCoordinatorForGroup || $is_host_of_group;

          function expandVolunteer($volunteers) {
              $ret = [];

              foreach ($volunteers as $volunteer) {
                  $volunteer['volunteer'] = $volunteer->volunteer;

                  if ($volunteer['volunteer']) {
                      $volunteer['userSkills'] = $volunteer->volunteer->userSkills->all();

                      foreach ($volunteer['userSkills'] as $skill) {
                          // Force expansion
                          $skill->skillName->skill_name;
                      }

                      $volunteer['fullName'] = $volunteer->name;
                      $volunteer['profilePath'] = '/uploads/thumbnail_' . $volunteer->volunteer->getProfile($volunteer->volunteer->id)->path;
                      $ret[] = $volunteer;
                  }
              }

              return $ret;
          }

          $expanded_volunteers = expandVolunteer($view_group->allConfirmedVolunteers);
//          ->take(20));

          ?>
      <div class="vue-placeholder vue-placeholder-large">
          <div class="vue-placeholder-content">@lang('partials.loading')...</div>
      </div>

      <div class="vue">
        <GroupHeading :group-id="{{ $group->idgroups }}" :group="{{ $group }}" :canedit="{{ $can_edit_group ? 'true' : 'false' }}" :ingroup="{{ $in_group ? 'true': 'false' }}"/>
      </div>

      <div class="d-flex flex-wrap">
          <div class="w-xs-100 w-md-50 vue">
              <GroupDescription class="pr-md-3" :group-id="{{ $group->idgroups }}" :group="{{ $group }}" />
          </div>
          <div class="w-xs-100 w-md-50 vue">
              <GroupVolunteers class="pl-md-3" :group-id="{{ $group->idgroups }}" :group="{{ $group }}" :volunteers="{{ json_encode($expanded_volunteers) }}" :canedit="{{ $can_edit_group ? 'true' : 'false' }}" />
          </div>
      </div>


      <div class="vue-placeholder vue-placeholder-large">
          <div class="vue-placeholder-content">@lang('partials.loading')...</div>
      </div>

      <div class="vue w-100 mt-md-50">
          <GroupStats :stats="{{ json_encode($group->getGroupStats((new App\Helpers\FootprintRatioCalculator())->calculateRatio())) }}" />
      </div>


      <div class="row mt-md-50">
        <div class="col-lg-12">
            <h2 id="upcoming-grp">@lang('groups.group_events')
              @if ( Auth::check() && $group->isVolunteer() )
                @php( $copy_link = url("/calendar/group/{$group->idgroups}") )
                @php( $user_edit_link = url("/profile/edit/{$user->id}#list-calendar-links") )
                @include('partials.calendar-feed-button', [
                  'copy_link' => $copy_link,
                  'user_edit_link' => $user_edit_link,
                  'modal_title' => 'Access all group events in your personal calendar',
                  'modal_text' => 'Add all of ' . $group->name . '\'s upcoming events to your Google/Outlook/Yahoo/Apple calendar with the link below:',
                ])
              @endif
            @if( FixometerHelper::hasRole( $user, 'Administrator' ) || FixometerHelper::hasRole( $user, 'Host' ) )<sup>(<a href="{{ url('/party/create') }}">Add event</a>)</sup>@endif</h2>

            <ul class="nav nav-tabs" id="myTab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="upcoming-past-tab" data-toggle="tab" href="#upcoming-past" role="tab" aria-controls="upcoming-past" aria-selected="true">@lang('groups.upcoming_active')</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="past-tab" data-toggle="tab" href="#past" role="tab" aria-controls="past" aria-selected="false">@lang('groups.past')</a>
              </li>
            </ul>
            <div class="tab-content" id="eventsTabContent">
              <div class="tab-pane fade show active" id="upcoming-past" role="tabpanel" aria-labelledby="upcoming-past-tab">

                <div class="events-list-wrap">
                  <div class="table-responsive">
                      <table class="table table-events table-striped" role="table">

                          @include('events.tables.headers.head-events-upcoming-only', ['hide-invite' => false, 'group_view' => true])

                          <tbody>

                            @if( !$upcoming_events->isEmpty() )
                              @foreach ($upcoming_events as $event)
                                @include('partials.tables.row-events', ['show_invites_count' => true, 'group_view' => true])
                              @endforeach
                            @else
                              <tr>
                                <td colspan="13" align="center" class="p-3">@lang('groups.no_upcoming_events')</td>
                              </tr>
                            @endif

                          </tbody>
                      </table>
                  </div>
                </div>

              </div>
              <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                <div class="events-list-wrap">
                  <div class="table-responsive">
                      <table class="table table-events table-striped" role="table">

                          @include('partials.tables.head-events', ['group_view' => true, 'hide_invite' => true])

                          <tbody>

                            @if( !$past_events->isEmpty() )
                              @foreach ($past_events as $event)
                                @include('partials.tables.row-events', ['group_view' => true, 'hide_invite' => true])
                              @endforeach
                            @else
                              <tr>
                                <td colspan="13" align="center" class="p-3">@lang('groups.no_past_events')</td>
                              </tr>
                            @endif

                          </tbody>
                      </table>
                  </div>
                </div>
              </div>

              <div class="events-link-wrap text-center">
                <a href="/party/group/{{{ $group->idgroups }}}">@lang('groups.see_all_events')</a>
              </div>
            </div>
        </div>
      </div>

      <?php
      $stats = [
          'fixed' => isset($group_device_count_status[0]) ? (int)$group_device_count_status[0]->counter : 0,
          'repairable' => isset($group_device_count_status[1]) ? (int)$group_device_count_status[1]->counter : 0,
          'dead' => isset($group_device_count_status[2]) ? (int)$group_device_count_status[2]->counter : 0
        ];

      $category_clusters = [
        1 => 'Computers and Home Office',
        2 => 'Electronic Gadgets',
        3 => 'Home Entertainment',
        4 => 'Kitchen and Household Items'
      ];

      $cluster_stats = [];

      foreach( $category_clusters as $key => $category_cluster ) {
          $fixed = isset($clusters['all'][$key][0]) ? (int)$clusters['all'][$key][0]->counter : 0;
          $repairable = isset($clusters['all'][$key][1]) ? (int)$clusters['all'][$key][1]->counter : 0;
          $dead = isset($clusters['all'][$key][2]) ? (int)$clusters['all'][$key][2]->counter : 0;
          $total = $clusters['all'][$key]['total'];

          //Seen and repaired stats
          if ( isset( $mostleast[$key]['most_seen'][0] ) ) {
              $most_seen = $mostleast[$key]['most_seen'][0]->name;
              $most_seen_type = $mostleast[$key]['most_seen'][0]->counter;
          } else {
              $most_seen = null;
              $most_seen_type = null;
          }

          if ( isset( $mostleast[$key]['most_repaired'][0] ) ) {
              $most_repaired = $mostleast[$key]['most_repaired'][0]->name;
              $most_repaired_type = $mostleast[$key]['most_repaired'][0]->counter;
          } else {
              $most_repaired = null;
              $most_repaired_type = null;
          }

          if ( isset( $mostleast[$key]['least_repaired'][0] ) ) {
              $least_repaired = $mostleast[$key]['least_repaired'][0]->name;
              $least_repaired_type = $mostleast[$key]['least_repaired'][0]->counter;
          } else {
              $least_repaired = null;
              $least_repaired_type = null;
          }

          $cluster_stats[$key] = [
              'fixed' => $fixed,
              'repairable' => $repairable,
              'dead' => $dead,
              'total' => $total,
              'most_seen' => [
                  'name' => $most_seen,
                  'count' => $most_seen_type
                ],
              'most_repaired' => [
                  'name' => $most_repaired,
                  'count' => $most_repaired_type
              ],
              'least_repaired' => [
                  'name' => $least_repaired,
                  'count' => $least_repaired_type
              ]
          ];
      }

      ?>

      <div class="d-flex flex-wrap flex-md-nowrap">
          <div class="vue w-100 mt-md-50 mr-md-4">
              <GroupDevicesWorkedOn :stats="{{ json_encode($stats) }}" class="mt-4" />
          </div>
          <div class="vue w-100 mt-md-50">
              <GroupDevicesMostRepaired :devices="{{ json_encode($top) }}" class="mt-3" />
          </div>
      </div>

      <div class="vue">
          <GroupDevicesBreakdown :cluster-stats="{{ json_encode($cluster_stats) }}" />
      </div>
  </div>
</section>

@include('includes/modals/group-invite-to')
@include('includes/modals/group-description')
@include('includes/modals/group-volunteers')
@include('includes/modals/group-share-stats')

@endsection
