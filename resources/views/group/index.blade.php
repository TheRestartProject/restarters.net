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
        function expandGroups($groups) {
            $ret = [];

            if ($groups) {
                foreach ($groups as $group) {
                    $group_image = $group->groupImage;

                    $event = $group->getNextUpcomingEvent();

                    $ret[] = [
                        'idgroups' => $group['idgroups'],
                        'name' => $group['name'],
                        'image' => (is_object($group_image) && is_object($group_image->image)) ?
                            asset('uploads/mid_'.$group_image->image->path) : null,
                        'location' => rtrim($group['location']),
                        'next_event' => $event ? $event['event_date'] : null,
                        'all_restarters_count' => $group->all_restarters_count,
                        'all_hosts_count' => $group->all_hosts_count,
                        'networks' => array_pluck($group->networks, 'id'),
                        'country' => $group->country,
                        'group_tags' => $group->group_tags()->get()->pluck('id')
                    ];
                }
            }

            return $ret;
        }

        $all_groups = expandGroups($groups);

        if (!is_null($your_groups)) {
            $your_groups = expandGroups($your_groups);
        }

        if (!is_null($groups_near_you)) {
            $groups_near_you = expandGroups($groups_near_you);
        }

        $can_create = FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'Host');
        $show_tags = FixometerHelper::hasRole(Auth::user(), 'Administrator');

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
