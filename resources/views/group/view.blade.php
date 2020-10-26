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

          $expanded_events = [];

          $footprintRatioCalculator = new App\Helpers\FootprintRatioCalculator();
          $emissionRatio = $footprintRatioCalculator->calculateRatio();

          foreach (array_merge($upcoming_events->all(), $past_events->all()) as $event) {
              $thisone = $event->getAttributes();
              $thisone['attending'] = Auth::user() && $event->isBeingAttendedBy(Auth::user()->id);
              $thisone['allinvitedcount'] = $event->allInvited->count();

              // TODO LATER Consider whether these stats should be in the event or passed into the store.
              $thisone['stats'] = $event->getEventStats($emissionRatio);
              $thisone['participants_count'] = $event->participants;
              $thisone['volunteers_count'] = $event->allConfirmedVolunteers->count();

              $thisone['isVolunteer'] = $event->isVolunteer();
              $thisone['requiresModeration'] = $event->requiresModerationByAdmin();
              $thisone['canModerate'] = Auth::user() && (FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'NetworkCoordinator'));

              $expanded_events[] = $thisone;
          }

          $showCalendar = Auth::check() && (($group && $group->isVolunteer()) || FixometerHelper::hasRole( Auth::user(), 'Administrator'));
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

      <div class="vue">
          <hr style="color: white; border-top: 1px solid black;" />
          <GroupEvents
                  heading-level="h2"
                  heading-sub-level="h2"
                  :group-id="{{ $group->idgroups }}"
                  :group="{{ $group }}"
                  :canedit="{{ $can_edit_group ? 'true' : 'false' }}"
                  :events="{{ json_encode($expanded_events) }}"
                  :limit="3"
                  calendar-copy-url="{{ $showCalendar ? url("/calendar/group/{$group->idgroups}") : '' }}"
                  calendar-edit-url="{{ $showCalendar ? url("/profile/edit/{$user->id}#list-calendar-links") : '' }}"
          />
      </div>

            <div class="row mt-md-50">

                <div class="col-lg-12">
                <h2 id="device-breakdown">@lang('groups.device_breakdown')</h2>

                <div class="row row-compressed-xs no-gutters">
                    <div class="col-lg-5">
                        <ul class="properties properties__small">
                            <li>
                                <div>
                                @php( $group_device_count = 0 )

                                @if (isset($group_device_count_status[0]))
                                  @php( $group_device_count = (int)$group_device_count_status[0]->counter )
                                @endif

                                @if (isset($group_device_count_status[1]))
                                  @php( $group_device_count += (int)$group_device_count_status[1]->counter )
                                @endif

                                @if (isset($group_device_count_status[2]))
                                  @php( $group_device_count += (int)$group_device_count_status[2]->counter )
                                @endif

                                <h3>@lang('groups.total_devices')</h3>
                                {{{ $group_device_count }}}
                                <svg width="18" height="16" viewBox="0 0 15 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M13.528,13.426l-12.056,0c-0.812,0 -1.472,-0.66 -1.472,-1.472l0,-7.933c0,-0.812 0.66,-1.472 1.472,-1.472l4.686,0l-1.426,-2.035c-0.059,-0.086 -0.039,-0.203 0.047,-0.263l0.309,-0.217c0.086,-0.06 0.204,-0.039 0.263,0.047l1.729,2.468l0.925,0l1.728,-2.468c0.06,-0.086 0.178,-0.107 0.263,-0.047l0.31,0.217c0.085,0.06 0.106,0.177 0.046,0.263l-1.425,2.035l4.601,0c0.812,0 1.472,0.66 1.472,1.472l0,7.933c0,0.812 -0.66,1.472 -1.472,1.472Zm-4.012,-9.499l-7.043,0c-0.607,0 -1.099,0.492 -1.099,1.099l0,5.923c0,0.607 0.492,1.099 1.099,1.099l7.043,0c0.606,0 1.099,-0.492 1.099,-1.099l0,-5.923c0,-0.607 -0.493,-1.099 -1.099,-1.099Zm3.439,3.248c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Zm0,-2.819c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Z" style="fill:#0394a6;"/></svg>
                                </div>
                            </li>
                            <li>
                                <div>
                                <h3>@lang('groups.fixed_devices')</h3>
                                @if (isset($group_device_count_status[0]))
                                  {{{ (int)$group_device_count_status[0]->counter }}}
                                @else
                                  0
                                @endif
                                <svg width="18" height="15" viewBox="0 0 14 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M6.601,1.38c1.344,-1.98 4.006,-1.564 5.351,-0.41c1.345,1.154 1.869,3.862 0,5.77c-1.607,1.639 -3.362,3.461 -5.379,4.615c-2.017,-1.154 -3.897,-3.028 -5.379,-4.615c-1.822,-1.953 -1.344,-4.616 0,-5.77c1.345,-1.154 4.062,-1.57 5.407,0.41Z" style="fill:#0394a6;"/></svg>
                                </div>
                            </li>
                            <li>
                                <div>
                                <h3>@lang('groups.repairable_devices')</h3>
                                @if (isset($group_device_count_status[1]))
                                  {{{ (int)$group_device_count_status[1]->counter }}}
                                @else
                                  0
                                @endif
                                <svg width="18" height="18" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.33,7.915l1.213,1.212c0.609,0.61 0.609,1.599 0,2.208l-2.208,2.208c-0.609,0.609 -1.598,0.609 -2.208,0l-1.212,-1.213l4.415,-4.415Zm-9.018,-6.811c0.609,-0.609 1.598,-0.609 2.207,0l1.213,1.213l-4.415,4.415l-1.213,-1.213c-0.609,-0.609 -0.609,-1.598 0,-2.207l2.208,-2.208Z" style="fill:#0394a6;"/><path d="M11.406,1.027c-0.61,-0.609 -1.599,-0.609 -2.208,0l-8.171,8.171c-0.609,0.609 -0.609,1.598 0,2.208l2.208,2.207c0.609,0.61 1.598,0.61 2.208,0l8.17,-8.17c0.61,-0.61 0.61,-1.599 0,-2.208l-2.207,-2.208Zm-4.373,8.359c0.162,-0.163 0.425,-0.163 0.588,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.162 -0.426,0.162 -0.588,0c-0.163,-0.162 -0.163,-0.426 0,-0.588Zm1.176,-1.177c0.163,-0.162 0.426,-0.162 0.589,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.163 -0.426,0.163 -0.589,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm-2.359,-0.006c0.162,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.426,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm3.536,-1.17c0.162,-0.163 0.426,-0.163 0.588,0c0.162,0.162 0.162,0.425 0,0.588c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.588Zm-2.359,-0.007c0.162,-0.162 0.426,-0.162 0.588,0c0.162,0.163 0.162,0.426 0,0.589c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.589Zm-2.361,-0.006c0.163,-0.163 0.426,-0.163 0.589,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.162 -0.426,0.162 -0.589,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm3.537,-1.17c0.162,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.426,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm-2.36,-0.007c0.163,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.425,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm1.177,-1.177c0.162,-0.162 0.426,-0.162 0.588,0c0.162,0.163 0.162,0.426 0,0.589c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.589Z" style="fill:#0394a6;"/></g></svg>
                                </div>
                            </li>
                            <li>
                                <div>
                                <h3>@lang('groups.end_of_life_devices')</h3>
                                @if (isset($group_device_count_status[2]))
                                  {{{ (int)$group_device_count_status[2]->counter }}}
                                @else
                                  0
                                @endif
                                <svg width="20" height="20" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M2.382,10.651c-0.16,0.287 -0.287,0.719 -0.287,0.991c0,0.064 0,0.144 0.016,0.256l-1.999,-3.438c-0.064,-0.112 -0.112,-0.272 -0.112,-0.416c0,-0.145 0.048,-0.32 0.112,-0.432l0.959,-1.679l-1.071,-0.607l3.486,-0.065l1.695,3.054l-1.087,-0.623l-1.712,2.959Zm1.536,-9.691c0.303,-0.528 0.8,-0.816 1.407,-0.816c0.656,0 1.168,0.305 1.535,0.927l0.544,0.912l-1.887,3.263l-3.054,-1.775l1.455,-2.511Zm0.223,12.457c-0.911,0 -1.663,-0.752 -1.663,-1.663c0,-0.256 0.112,-0.688 0.272,-0.96l0.512,-0.911l3.79,0l0,3.534l-2.911,0l0,0Zm3.039,-12.553c-0.24,-0.415 -0.559,-0.704 -0.943,-0.864l3.933,0c0.352,0 0.624,0.144 0.784,0.417l0.976,1.662l1.055,-0.624l-1.696,3.039l-3.469,-0.049l1.071,-0.607l-1.711,-2.974Zm6.061,9.051c0.479,0 0.88,-0.128 1.215,-0.383l-1.983,3.453c-0.16,0.272 -0.447,0.432 -0.783,0.432l-1.872,0l0,1.231l-1.791,-2.99l1.791,-2.991l0,1.248l3.423,0l0,0Zm1.534,-2.879c0.145,0.256 0.225,0.528 0.225,0.816c0,0.576 -0.368,1.183 -0.879,1.471c-0.241,0.128 -0.577,0.209 -0.912,0.209l-1.056,0l-1.886,-3.263l3.054,-1.743l1.454,2.51Z" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg>
                                </div>
                            </li>
                            <li class="properties__item__full">
                                <div>
                                <h3>@lang('groups.most_repaired_devices')</h3>
                                <div class="row row-compressed properties__repair-count">

                                    @for ($i=0; $i < 3; $i++)
                                      @if (isset($top[$i]))
                                        <div class="col-6"><strong>{{{ $top[$i]->name }}}: </strong></div>
                                        <div class="col-6">{{{ $top[$i]->counter }}}</div>
                                      @else
                                        <div class="col-12"><strong>{{{ $i+1 }}}.</strong> N/A</div>
                                      @endif
                                    @endfor

                                </div>
                                <svg width="18" height="16" viewBox="0 0 15 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M13.528,13.426l-12.056,0c-0.812,0 -1.472,-0.66 -1.472,-1.472l0,-7.933c0,-0.812 0.66,-1.472 1.472,-1.472l4.686,0l-1.426,-2.035c-0.059,-0.086 -0.039,-0.203 0.047,-0.263l0.309,-0.217c0.086,-0.06 0.204,-0.039 0.263,0.047l1.729,2.468l0.925,0l1.728,-2.468c0.06,-0.086 0.178,-0.107 0.263,-0.047l0.31,0.217c0.085,0.06 0.106,0.177 0.046,0.263l-1.425,2.035l4.601,0c0.812,0 1.472,0.66 1.472,1.472l0,7.933c0,0.812 -0.66,1.472 -1.472,1.472Zm-4.012,-9.499l-7.043,0c-0.607,0 -1.099,0.492 -1.099,1.099l0,5.923c0,0.607 0.492,1.099 1.099,1.099l7.043,0c0.606,0 1.099,-0.492 1.099,-1.099l0,-5.923c0,-0.607 -0.493,-1.099 -1.099,-1.099Zm3.439,3.248c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Zm0,-2.819c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Z" style="fill:#0394a6;"/></svg>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="col-lg-7">
                        @include('partials.group-device-breakdown')
                    </div>
                </div>

            </div>
        </div>
  </div>
</section>

@include('includes/modals/group-invite-to')
@include('includes/modals/group-description')
@include('includes/modals/group-volunteers')
@include('includes/modals/group-share-stats')

@endsection
