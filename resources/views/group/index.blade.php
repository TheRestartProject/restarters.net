@extends('layouts.app')
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
          <div class="btn-group">
            @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'Host') )
              <a href="{{{ route('create-group') }}}" class="btn btn-primary btn-save">@lang('groups.create_groups')</a>
            @endif
          </div>
        </div>

      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-12">

        @if( !is_null($your_groups) )
          <section class="table-section" id="your-groups">

            <h2>@lang('groups.groups_title1')</h2>

            <table role="table" class="table table-striped table-hover">
              @include('partials.tables.head-groups')
              <tbody>
                @if( !$your_groups->isEmpty() )
                  @foreach ($your_groups as $group)

                    @include('partials.tables.row-groups')

                  @endforeach
                @else
                  <tr>
                    <td colspan="13" align="center" class="p-3">
                      You are not associated with any groups, take a look and see if there's one you would like to join
                      @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'Host') )
                        <br><a href="/group/all">See all groups</a>
                      @endif
                    </td>
                  </tr>
                @endif
              </tbody>
            </table>

          </section>
        @endif

        <section class="table-section" id="your-groups">

          <h2>@lang('groups.groups_title2') <sup>(<a href="/group/all">See all groups</a>)</sup></h2>

          <table role="table" class="table table-striped table-hover">
            @include('partials.tables.head-groups')
            <tbody>
              @if( !is_null($groups_near_you) )
                @foreach ($groups_near_you as $group)

                  @include('partials.tables.row-groups')

                @endforeach
              @else
                <tr>
                  <td colspan="13" align="center" class="p-3">
                    There are no groups within your area, would you consider starting a group?
                    <br><a href="/profile/edit/{{{ Auth::user()->id }}}">@lang('groups.create_groups')</a>
                  </td>
                </tr>
              @endif
            </tbody>
          </table>

        </section>

        @if( !is_null($groups) )
          <section class="table-section" id="your-groups">

            <h2>@lang('groups.groups_title3')</h2>

            <table role="table" class="table table-striped table-hover">
              @include('partials.tables.head-groups')
              <tbody>
                @if( !$groups->isEmpty() )
                  @foreach ($groups as $group)

                    @include('partials.tables.row-groups')

                  @endforeach
                @else
                  <tr>
                    <td colspan="13" align="center" class="p-3">There are no groups</td>
                  </tr>
                @endif
              </tbody>
            </table>

          </section>

          <div class="d-flex justify-content-center">
            <nav aria-label="Page navigation example">
              {!! $groups->links() !!}
            </nav>
          </div>
        @endif

      </div>
    </div>

  </div>
</section>
@endsection
