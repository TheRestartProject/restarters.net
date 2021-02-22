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
            :past-events="{{ json_encode($past_events, JSON_INVALID_UTF8_IGNORE) }}"
            :topics="{{ json_encode($topics, JSON_INVALID_UTF8_IGNORE) }}"
            see-all-topics-link="{{ $seeAllTopicsLink }}"
            :is-logged-in="{{ Auth::check() ? 'true' : 'false'  }}"
            discourse-base-url="{{ env('DISCOURSE_URL') }}"
        />
      </div>
    </div>
  </div>

  </div>
<section>
@endsection
