@extends('layouts.app')

@section('title')
    Dashboard
@endsection

@section('content')
<section class="dashboard">
  <div class="container">
  <div class="row row-compressed">
      <div class="col">
            <h1 id="dashboard__header">@lang('dashboard.title')</h1>
            <p>@lang('dashboard.subtitle')</p>
      </div>
  </div>

  {{-- temporary banner --}}
  <div class="row row-compressed">
      <div class="col">
          @if ( is_null(Cookie::get("information-alert-dismissed-onlineevents")) && Auth::check() )

              <div class="alert alert-secondary information-alert alert-dismissible fade show " role="alert" id="onlineevents">
                <div class="d-sm-flex flex-row justify-content-between align-items-center">
                  <div class="action-text-left float-left d-flex flex-row">
                      <span class="icon my-auto d-none">@include('partials.svg-icons.calendar-icon-lg')</span>
                      <div class="action-text mb-0">
                          <div class='mb-2'>
                            <span class='badge badge-warning'>NEW!</span>
                            <strong>Recording unpowered items</strong>
                          </div>
                          <p>
                              It is now possible to record repairs of non-electronic/electrical items at your events!
                          </p>
                      </div>
                  </div>

                  <div class="float-right mt-3 mt-sm-0">
                      @php( $user = Auth::user() )
                      <a href='https://talk.restarters.net/t/you-can-now-record-unpowered-items-in-the-fixometer/3388' class='btn btn-md btn-primary btn-block' title=''>Learn how here</a>
                      <button type="button" class="close set-dismissed-cookie float-none ml-2" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                      </button>
                  </div>
                </div>
              </div>

          @endif

      </div>
  </div>
  {{-- temporary banner --}}

  <div class="row row-compressed">
      @if ($show_getting_started)
          @include('dashboard.blocks.getting-started')
      @endif
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
