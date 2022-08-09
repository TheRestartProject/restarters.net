@extends('layouts.app')

@section('title')
{{ $network->name }}
@endsection

@section('content')
<section class="events networks">
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

        <div class="events__header row  no-gutters">
            <div class="col d-flex flex-column">
                <header>
                    <div class="row">
                        <div class="col-lg-3 align-self-center" style="text-align:center">
                            <div class="network-icon">
                                @php( $logo = $network->logo )
                                @if( is_object($logo) && is_object($logo->image) )
                                <img style="max-width: 100%; max-height:50px" src="{{ asset('/uploads/mid_'. $logo->image->path) }}" alt="{{{ $network->name }}} logo">
                                @else
                                <img src="{{ url('/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg') }}" alt="generic network logo">
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <h1>{{{ $network->name }}}</h1>

                            @if( !empty($network->website) )
                            <a class="events__header__url" href="{{{ $network->website }}}" target="_blank" rel="noopener noreferrer">{{{ $network->website }}}</a>
                            @endif

                        </div>
                        <div class="col-lg-4">
                            <div class="button-group button-group__r">
                                @if( Auth::check() )
                                <div class="dropdown">
                                    <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Network actions
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="/group/network/{{ $network->id }}">@lang('networks.show.view_groups_menuitem')</a>
                                        @can('associateGroups', $network)
                                        <button data-toggle="modal" data-target="#network-add-group" class="dropdown-item">@lang('networks.show.add_groups_menuitem')</button>
                                        @endcan
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                </header>
            </div>
        </div>


        <div class="row">
            <div class="col-lg-3">

                <h2 id="about-grp">About</h2>

                <div class="events__description">
                    <p>{!! Str::limit(strip_tags($network->description), 160, '...') !!}</p>
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

                <section style="mt-40 mb-40">
                    <h2>Groups</h2>

                    <div class="panel">
                    <p>
                        There are currently {{ $network->groups->count() }} groups in the {{ $network->name }} network. <a href="/group/network/{{ $network->id }}">View these groups</a>.
                    </p>
                    </div>

                </section>

                <h2 class="mt-4">@lang('groups.groups_title_admin')</h2>
                <section class="table-section" id="events-1">
                    <div class="vue-placeholder vue-placeholder-large">
                        <div class="vue-placeholder-content">@lang('partials.loading')...</div>
                    </div>
                    <div class="vue">
                        <GroupsRequiringModeration />
                    </div>
                </section>

                <h2 class="mt-4">@lang('events.events_title_admin')</h2>
                <section class="mt-40">
                    <div class="vue-placeholder vue-placeholder-large">
                        <div class="vue-placeholder-content">@lang('partials.loading')...</div>
                    </div>
                    <div class="vue">
                        <EventsRequiringModeration />
                    </div>
                </section>

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


@can('associateGroups', $network)
@include('networks.partials.add-group-modal')
@endcan('associateGroups', $network)

@endsection
