@extends('layouts.app')

@section('content')
<section class="events networks">
    <div class="container-fluid">
      <div class="events__header row align-content-top">
          <div class="col d-flex flex-column">

            <header>
                <div class="row">
                <div class="col col-md-3 network-icon" style="text-align:center">
                    <img style="max-height:50px" src="{{ asset('images/logos/'.$network->shortname.'.png') }}">
                </div>

                <div class="col col-md-9">
                <h1>{{{ $network->name }}}</h1>

                @if( !empty($network->website) )
                    <a class="events__header__url" href="{{{ $network->website }}}" target="_blank" rel="noopener noreferrer">{{{ $network->website }}}</a>
                @endif

                     {{-- @php( $groupImage = $group->groupImage )
                     @if( is_object($groupImage) && is_object($groupImage->image) )
                  <img src="{{ asset('/uploads/mid_'. $groupImage->image->path) }}" alt="{{{ $group->name }}} group image" class="event-icon">
                @else

                  <img src="{{ url('/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg') }}" alt="{{{ $group->name }}} group image" class="event-icon">
                @endif
                --}}
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
                </section>


        {{-- Events to Moderate (Admin Only) --}}
            <h2>@lang('events.events_title_admin')</h2>

            <div class="table-responsive">
              <table class="table table-events table-striped" role="table">
                @include('events.tables.headers.head-events-admin-only', ['hide_invite' => true])
                <tbody>
                    @if( count($network->eventsRequiringModeration()) > 0 )
                      @foreach ($network->eventsRequiringModeration() as $event)
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
        {{-- END Events to Moderate (Admin Only) --}}

            </div>
        </div>

    </div>
</section>
@include('includes/modals/group-description')

@endsection

