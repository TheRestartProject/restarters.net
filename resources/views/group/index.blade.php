@extends('layouts.app')
@section('content')

<section class="groups">
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="d-flex justify-content-between align-content-center">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">FIXOMETER</a></li>
              <li class="breadcrumb-item active" aria-current="page">@lang('groups.groups')</li>
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

        <section class="table-section" id="your-groups">

          <h2>@lang('groups.groups_title3')</h2>

          <table role="table" class="table table-striped table-hover">
            <thead>
              <tr>
                <th></th>
                <th scope="col">@lang('groups.groups_name')</th>
                <th scope="col">@lang('groups.groups_location')</th>
                <th scope="col" class="text-center">@lang('groups.groups_hosts')</th>
                <th scope="col" class="text-center">@lang('groups.groups_restarters')</th>
              </tr>
            </thead>
            <tbody>

              @foreach($groups as $group)
              <tr>
                <td class="table-cell-icon">
                  @php( $group_image = $group->groupImage )
                  @if( is_object($group_image) && is_object($group_image->image) )
                    <img src="{{ asset('/uploads/thumbnail_' . $group_image->image->path) }}" alt="{{{ $group->name }}}">
                  @else
                    <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $group->name }}}">
                  @endif
                </td>
                <td><a href="/group/view/{{{ $group->idgroups }}}" title="edit group">{{{ $group->name }}}</a></td>
                <td>{{{ $group->location . ', ' . $group->area }}}</td>
                <td class="text-center">{{{ $group->allHosts->count() }}}</td>
                <td class="text-center">{{{ $group->allRestarters->count() }}}</td>
              </tr>
              @endforeach

            </tbody>
          </table>

        </section>

        <div class="d-flex justify-content-center">
          <nav aria-label="Page navigation example">
            {!! $groups->links() !!}
          </nav>
        </div>

      </div>
    </div>

  </div>
</section>
@endsection
