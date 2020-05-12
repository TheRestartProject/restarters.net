@extends('layouts.app')

@section('title')
    {{ $network->name }}
@endsection

@section('content')
<section class="events networks">
    <div class="container-fluid">
        @if (\Session::has('success'))
            <div class="alert alert-success">
                {!! \Session::get('success') !!}
            </div>
        @endif

      <div class="events__header row align-content-top">
          <div class="col d-flex flex-column">

            <header>
                <div class="row">
                <div class="col col-md-3 align-self-center" style="text-align:center">
                    <div class="network-icon">
                    @php( $logo = $network->logo )
                    @if( is_object($logo) && is_object($logo->image) )
                        <img style="max-height:50px" src="{{ asset('/uploads/mid_'. $logo->image->path) }}" alt="{{{ $network->name }}} logo">
                    @else
                        <img src="{{ url('/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg') }}" alt="generic network logo">
                    @endif
                    </div>
                </div>

                <div class="col col-md-9">
                <h1>{{{ $network->name }}}</h1>

                @if( !empty($network->website) )
                    <a class="events__header__url" href="{{{ $network->website }}}" target="_blank" rel="noopener noreferrer">{{{ $network->website }}}</a>
                @endif

                </div>
                </div>

            </header>
          </div>
      </div>


        <div class="row">
            <div class="col-lg-3">

                <h2 id="about-grp">About</h2>

                <div class="events__description">
                    <p>{!! str_limit(strip_tags($network->description), 160, '...') !!}</p>
                    @if( strlen($network->description) > 160 )
                      <button data-toggle="modal" data-target="#group-description"><span>Read more</span></button>
                    @endif
                </div><!-- /events__description -->


                <h2 id="volunteers">Coordinators</h2>

                <div class="tab">

                    <div class="users-list-wrap users-list__single">
                        <ul class="users-list">

                            @foreach ($network->coordinators as $coordinator)
                                @include('networks.partials.coordinator')
                            @endforeach

                        </ul>
                    </div>

                </div>

            </div>
            <div class="col-lg-9">

                <section style="margin-bottom:40px">
                    <h2>Groups</h2>

                    <p>
                        There are currently {{ $network->groups->count() }} groups in the {{ $network->name }} network. <a href="/group/all/search?network={{ $network->id }}">View these groups</a>.
                    </p>


                    @can('associateGroups', $network)

                <form action="/networks/{{ $network->id }}/groups/" method="post">

                        @csrf
                <div class="form-group form-group__offset">
                  <label for="group">@lang('networks.show.addGroup'):</label>
                  <div class="form-control form-control__select">
                    <select name="group" id="group" class="select2" required>
                      <option></option>
                        @foreach($groupsForAssociating as $group)
                            <option value="{{{ $group->idgroups }}}">{{{ $group->name }}}</option>
                        @endforeach
                    </select>
                  </div>
                  <input type="submit" class="btn btn-primary" id="create-event" value="@lang('networks.show.addGroupButton')">
                </div>
                </form>
                    @endcan
                </section>


            <h2>@lang('events.events_title_admin')</h2>

            <div class="table-responsive">
              <table class="table table-events table-striped" role="table">
                @include('events.tables.headers.head-events-admin-only', ['hide_invite' => true])
                <tbody>
                    @if( count($network->eventsRequiringModeration()) > 0 )
                      @foreach ($network->eventsRequiringModeration()->sortBy('event_date') as $event)
                        @include('partials.tables.row-events', ['show_invites_count' => false])
                      @endforeach
                    @else
                      <tr>
                        <td colspan="13" align="center" class="p-3">@lang('events.moderation_none')</td>
                      </tr>
                    @endif
                </tbody>
              </table>
            </div>

            </div>
        </div>

    </div>
</section>

<!-- Modal -->
<div class="modal modal__description fade" id="group-description" tabindex="-1" role="dialog" aria-labelledby="groupDescriptionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5 id="groupDescriptionLabel">@lang('networks.show.about_modal_header', ['name' => $network->name])</h5>
                @include('partials.cross')

            </div>

            <div class="modal-body">

                {!! $network->description !!}

            </div>


        </div>
    </div>
</div>

@endsection

