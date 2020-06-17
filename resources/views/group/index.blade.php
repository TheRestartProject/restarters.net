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

      <div class="row mb-30">
          <div class="col-12 col-md-12">
              <div class="d-flex align-items-center">
                  <h1 class="mb-0 mr-30">
                      Groups
                  </h1>

                  <div class="mr-auto d-none d-md-block">
                      @include('svgs.group.group-doodle')
                  </div>

                  @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'Host') )
                      <a href="{{{ route('create-group') }}}" class="btn btn-primary ml-auto">
                          <span class="d-none d-lg-block">@lang('groups.create_groups')</span>
                          <span class="d-block d-lg-none">@lang('groups.create_groups_mobile')</span>
                      </a>
                  @endif
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
        <form action="/group/" method="get" id="device-search">
          <input type="hidden" name="sort_direction" value="{{$sort_direction}}" class="sr-only">
          <input type="radio" name="sort_column" value="upcoming_event" @if( $sort_column == 'upcoming_event' ) checked @endif id="label-upcoming_event" class="sr-only">

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
        </form>
        @php( $user_preferences = session('column_preferences') )
      @endif

    </section>
  @endsection
