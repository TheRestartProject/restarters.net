@extends('layouts.app')
@section('title')
    {{ $group->name }}
@endsection
@section('content')
<section class="events group-view">
  <div class="container">

      <?php if (isset($_GET['message']) && $_GET['message'] == 'invite'): ?>
        <div class="alert alert-info" role="alert">
          Thank you, your invitation has been sent
        </div>
      <?php endif; ?>

      @if(session()->has('response'))
        @php( App\Helpers\Fixometer::printResponse(session('response')) )
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
          <div class="alert alert-warning">
              {!! __('groups.invitation_pending', [
                'accept' => '/group/accept-invite/' . $group->idgroups . '/' . $has_pending_invite
              ]) !!}
          </div>
      @endif

      <?php
          // Trigger expansion of group.
          $group_image = $group->groupImage;
          if (is_object($group_image) && is_object($group_image->image)) {
              $group_image->image->path;
          }

          $can_edit_group = App\Helpers\Fixometer::hasRole($user, 'Administrator') || $isCoordinatorForGroup || $is_host_of_group;
          $can_demote = App\Helpers\Fixometer::hasRole($user, 'Administrator') || $isCoordinatorForGroup;
          $can_see_delete = App\Helpers\Fixometer::hasRole($user, 'Administrator');
          $can_perform_delete = $can_see_delete && $group->canDelete();
          $can_perform_archive = App\Helpers\Fixometer::hasRole($user, 'Administrator') || $isCoordinatorForGroup;

          $showCalendar = Auth::check() && (($group && $group->isVolunteer()) || App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator'));

          $device_stats = [
              'fixed' => 0,
              'repairable' => 0,
              'dead' => 0
          ];

          foreach ($group_device_count_status as $count) {
              if ($count->status == \App\Device::REPAIR_STATUS_FIXED) {
                  $device_stats['fixed'] = $count->counter;
              } else if ($count->status == \App\Device::REPAIR_STATUS_REPAIRABLE) {
                  $device_stats['repairable'] = $count->counter;
              } else if ($count->status == \App\Device::REPAIR_STATUS_ENDOFLIFE) {
                  $device_stats['dead'] = $count->counter;
              }
          }

          $category_clusters = [
            1 => 'Computers and Home Office',
            2 => 'Electronic Gadgets',
            3 => 'Home Entertainment',
            4 => 'Kitchen and Household Items',
          ];

          $cluster_stats = [];

          foreach ($category_clusters as $key => $category_cluster) {
              $fixed = isset($clusters['all'][$key][0]) ? (int) $clusters['all'][$key][0]['counter'] : 0;
              $repairable = isset($clusters['all'][$key][1]) ? (int) $clusters['all'][$key][1]['counter'] : 0;
              $dead = isset($clusters['all'][$key][2]) ? (int) $clusters['all'][$key][2]['counter'] : 0;
              $total = $clusters['all'][$key]['total'] ? $clusters['all'][$key]['total'] : 0;

              //Seen and repaired stats
              if (isset($mostleast[$key]['most_seen'][0])) {
                  $most_seen = $mostleast[$key]['most_seen'][0]->name;
                  $most_seen_type = $mostleast[$key]['most_seen'][0]->counter;
              } else {
                  $most_seen = null;
                  $most_seen_type = 0;
              }

              if (isset($mostleast[$key]['most_repaired'][0])) {
                  $most_repaired = $mostleast[$key]['most_repaired'][0]->name;
                  $most_repaired_type = $mostleast[$key]['most_repaired'][0]->counter;
              } else {
                  $most_repaired = null;
                  $most_repaired_type = 0;
              }

              if (isset($mostleast[$key]['least_repaired'][0])) {
                  $least_repaired = $mostleast[$key]['least_repaired'][0]->name;
                  $least_repaired_type = $mostleast[$key]['least_repaired'][0]->counter;
              } else {
                  $least_repaired = null;
                  $least_repaired_type = 0;
              }

              $cluster_stats[$key] = [
                  'fixed' => $fixed,
                  'repairable' => $repairable,
                  'dead' => $dead,
                  'total' => $total,
                  'most_seen' => [
                      'name' => $most_seen,
                      'count' => $most_seen_type,
                    ],
                  'most_repaired' => [
                      'name' => $most_repaired,
                      'count' => $most_repaired_type,
                  ],
                  'least_repaired' => [
                      'name' => $least_repaired,
                      'count' => $least_repaired_type,
                  ],
              ];
          }

          $in_group = \App\UserGroups::where('group', $group->idgroups)
              ->where('user', Auth::id())
              ->where('status', 1)
              ->whereNull('users_groups.deleted_at')
              ->exists();

          $discourseGroup = $group->discourse_group ? (env('DISCOURSE_URL').'/g/'.$group->discourse_group) : null;

          ?>

      <div class="vue-placeholder vue-placeholder-large">
          <div class="vue-placeholder-content">@lang('partials.loading')...</div>
      </div>
      <div class="vue">
          <GroupPage
              csrf="{{ csrf_token() }}"
              :idgroups="{{ $group->idgroups }}"
              :initial-group="{{ $group }}"
              :group-stats="{{ json_encode($group_stats, JSON_INVALID_UTF8_IGNORE) }}"
              :device-stats="{{ json_encode($device_stats, JSON_INVALID_UTF8_IGNORE) }}"
              :cluster-stats="{{ json_encode($cluster_stats, JSON_INVALID_UTF8_IGNORE) }}"
              :top-devices="{{ json_encode($top, JSON_INVALID_UTF8_IGNORE) }}"
              :events="{{ json_encode($expanded_events, JSON_INVALID_UTF8_IGNORE) }}"
              :canedit="{{ $can_edit_group ? 'true' : 'false' }}"
              :candemote="{{ $can_demote ? 'true' : 'false' }}"
              :can-see-delete="{{ $can_see_delete ? 'true' : 'false' }}"
              :can-perform-delete="{{ $can_perform_delete ? 'true' : 'false' }}"
              :can-perform-archive="{{ $can_perform_archive ? 'true' : 'false' }}"
              calendar-copy-url="{{ $showCalendar ? url("/calendar/group/{$group->idgroups}") : '' }}"
              calendar-edit-url="{{ $showCalendar ? url("/profile/edit/{$user->id}#list-calendar-links") : '' }}"
              :ingroup="{{ $in_group ? 'true' : 'false' }}"
              discourse-group="{{ $discourseGroup }}"
          />
      </div>
  </div>
</section>

@include('includes/modals/group-invite-to')
@include('includes/modals/group-description')
@include('includes/modals/group-share-stats')

@endsection
