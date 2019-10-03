@extends('layouts.app')

@section('title')
    Dashboard
@endsection

@section('content')
<section class="dashboard">
  <div class="container-fluid">
  <div class="row row-compressed">
      <div class="col">
          <div style="padding-left:10px">
            <h1 id="dashboard__header">@lang('dashboard.title')</h1>
            <p>@lang('dashboard.subtitle')</p>
          </div>
      </div>
  </div>

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
        <div class="col-12">
            @include('dashboard.blocks.impact')
        </div>
      </div>
    </div>
  </div>

  </div>
<section>
@endsection
