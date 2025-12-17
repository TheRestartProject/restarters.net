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
        <div class="vue">
            <NetworkPage
                :network="{{ json_encode($networkData) }}"
                :initial-stats="{{ json_encode($stats) }}"
                :initial-tags="{{ json_encode($tags) }}"
                :can-manage-tags="{{ json_encode($canManageTags) }}"
                :can-associate-groups="{{ json_encode($canAssociateGroups) }}"
                :is-logged-in="{{ json_encode(Auth::check()) }}"
                api-token="{{ $apiToken }}"
            />
        </div>

    </div>
</section>

@can('associateGroups', $network)
@include('networks.partials.add-group-modal')
@endcan('associateGroups', $network)

@endsection
