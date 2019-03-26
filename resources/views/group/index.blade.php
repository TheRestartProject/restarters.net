@extends('layouts.app')

@section('title')
  Groups
@endsection

@section('content')

  <section class="groups">
    <div class="container">
      <div class="row">
        <div class="col">
          <div class="d-flex justify-content-between align-content-center">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                @if( !is_null($your_groups) )
                  <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">FIXOMETER</a></li>
                  <li class="breadcrumb-item active" aria-current="page">@lang('groups.groups')</li>
                @else
                  <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">FIXOMETER</a></li>
                  <li class="breadcrumb-item"><a href="{{ route('groups') }}">@lang('groups.groups')</a></li>
                  <li class="breadcrumb-item active" aria-current="page">All groups</li>
                @endif
              </ol>
            </nav>
            <div class="btn-group button-group-filters">
              <button class="reveal-filters btn btn-secondary d-lg-none d-xl-none" type="button" data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter">Reveal filters</button>
              @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'Host') )
                <a href="{{{ route('create-group') }}}" class="btn btn-primary btn-save">@lang('groups.create_groups')</a>
              @endif
            </div>
          </div>

        </div>
      </div>

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
        @if( !is_null($your_groups) )
           <div class="row">
             <div class="col">
               @include('group.sections.user-groups')
             </div>
           </div>
        @endif

        @if( is_null($groups) )
          <div class="row">
            <div class="col">
              @include('group.sections.groups-nearby')
            </div>
          </div>
        @endif

        @php( $user_preferences = session('column_preferences') )

      @endif

    </section>
  @endsection
