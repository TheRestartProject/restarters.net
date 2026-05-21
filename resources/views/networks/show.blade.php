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

        <div class="vue-placeholder vue-placeholder-large">
            <div class="vue-placeholder-content">@lang('partials.loading')...</div>
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

                <section class="mt-4 mb-4">
                    <h2>{{ __('networks.general.groups') }}</h2>

                    <div class="panel vue">
                        <GroupMapAndList
                            :network="{{ $network->id }}"
                            :initial-bounds="{{ json_encode($mapBounds) }}"
                            :show-filters="true"
                            :can-manage-tags="{{ json_encode($canManageTags) }}"
                            :available-tags="{{ json_encode($tags) }}"
                            fetch-groups
                        />
                    </div>

                </section>

            </div>
        </div>

    </div>
</section>

@can('associateGroups', $network)
@include('networks.partials.add-group-modal')
@endcan('associateGroups', $network)

@endsection
