@extends('layouts.app')

@section('title')
    Dashboard
@endsection

@section('content')
<section class="dashboard">
  <div class="container">

  <div class="row row-compressed">
    <div class="col">
        @if (session('response'))
            <div class="row row-compressed">
                <div class="col">
                    @foreach (session('response') as $key => $message)
                        <div class="alert alert-{{ $key }}">
                            {{ $message }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

      @if (session('invites-feedback'))
        <div class="row row-compressed">
          <div class="col">
            <ul class="alert alert-success list-unstyled">
              @foreach (session('invites-feedback') as $key => $message)
                   <li>{!! $message !!}</li>
               @endforeach
            </ul>
          </div>
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

        ?>

      <div class="vue-placeholder vue-placeholder-large">
        <div class="vue-placeholder-content">@lang('partials.loading')...</div>
      </div>

      <div class="vue">
        <DashboardPage
            csrf="{{ csrf_token() }}"
            administrator="{{ FixometerHelper::hasRole($user, 'Administrator') ? 'true' : 'false'}}"
            host="{{ FixometerHelper::hasRole($user, 'Host') ? 'true' : 'false'}}"
            restarter="{{ FixometerHelper::hasRole($user, 'Restarter') ? 'true' : 'false'}}"
            network-coordinator="{{ FixometerHelper::hasRole($user, 'NetworkCoordinator') ? 'true' : 'false'}}"
            :your-groups="{{ json_encode($your_groups, JSON_INVALID_UTF8_IGNORE) }}"
            :upcoming-events="{{ json_encode($upcoming_events, JSON_INVALID_UTF8_IGNORE) }}"
        />
      </div>

      <div class="row row-compressed">
        @if (FixometerHelper::hasRole($user, 'Administrator'))
          @include('dashboard.restarter')
        @endif
        @if (FixometerHelper::hasRole($user, 'Host'))
          @include('dashboard.host')
        @endif
        @if (FixometerHelper::hasRole($user, 'Restarter'))
          @include('dashboard.restarter')
        @endif
        @if (FixometerHelper::hasRole($user, 'NetworkCoordinator'))
            @include('dashboard.coordinator')
        @endif
        <div class="col-12">
            @include('dashboard.blocks.impact')
        </div>
      </div>
    </div>
  </div>

  </div>
<section>
@endsection
