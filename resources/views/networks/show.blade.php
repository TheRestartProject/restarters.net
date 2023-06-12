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
                                @php( $logo = $network->sizedLogo('_x100') )
                                @if( $logo )
                                <img style="max-width: 100%; max-height:50px" src="{{ asset("/uploads/$logo") }}" alt="{{{ $network->name }}} logo">
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
                                    <button class="btn btn-primary dropdown-toggle text-uppercase" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        {{ __('networks.general.actions') }}
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

                <h2 id="about-grp">{{ __('networks.general.about') }}</h2>

                <div class="events__description">
                    @if(strlen($network->description) <= 160)
                        {!! $network->description !!}
                    @else
                        <p>{!! Str::limit(strip_tags($network->description), 160, '...') !!}</p>
                        @if( strlen($network->description) > 160 )
                        <button data-toggle="modal" data-target="#group-description"><span>{{ __('partials.read_more') }}</span></button>
                        @endif
                    @endif
                </div><!-- /events__description -->


                <h2 id="volunteers">{{ __('networks.general.coordinators') }}</h2>

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
                    <h2>{{ __('networks.general.groups') }}</h2>

                    <div class="panel">
                    <p>
                        {!! __('networks.general.count', [
                            'count' => $network->groups->count(),
                            'name' => $network->name,
                            'id' => $network->id
                        ]) !!}
                    </p>
                    </div>

                </section>

                <div class="vue-placeholder vue-placeholder-large">
                    <div class="vue-placeholder-content">@lang('partials.loading')...</div>
                </div>
                <div class="vue">
                    <GroupsRequiringModeration :networks="[{{ $network->id }}]" />
                </div>

                <div class="vue-placeholder vue-placeholder-large">
                    <div class="vue-placeholder-large">@lang('partials.loading')...</div>
                </div>
                <div class="vue">
                    <EventsRequiringModeration :networks="[{{ $network->id }}]" />
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


@can('associateGroups', $network)
@include('networks.partials.add-group-modal')
@endcan('associateGroups', $network)

@endsection
