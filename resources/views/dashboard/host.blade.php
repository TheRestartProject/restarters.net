@if ($in_group)
    <div class="col-md-12 col-lg-12 col-xl-6 col-dashboard">
        @include('partials.upcoming2')
    </div>
@else
    <div class="col-md-12 col-lg-12 col-xl-6 col-dashboard">
        @include('dashboard.blocks.groups-near-you')
    </div>
@endif

<div class="col-md-12 col-lg-12 col-xl-6 col-dashboard">
    @include('dashboard.blocks.hot-topics')
</div>

<div class="col-md-12 col-lg-4 col-xl-4 col-dashboard">
    @include('dashboard.blocks.discussion')
</div>

@if ($user->number_of_logins <= 3)
    <div class="col-md-12 col-lg-4 col-xl-4 col-dashboard">@include('partials.welcome-materials-host')</div>
@else
    <div class="col-md-12 col-lg-4 col-xl-4 col-dashboard">@include('partials.how-to')</div>
    <div class="col-md-6 col-lg-6 col-xl-4 col-dashboard">@include('partials.past')</div>
    @if (!empty($inactive_groups))
        <div class="col-md-6 col-lg-6 col-xl-4 col-dashboard">@include('partials.create-event')</div>
    @endif
    @if (!empty($outdated_groups))
        <div class="col-lg-6 col-xl-3 col-dashboard">@include('partials.up-to-date')</div>
    @endif
@endif

<div class="col-md-6 col-lg-6 col-xl-4 col-dashboard">@include('dashboard.blocks.wiki')</div>
<div class="col-md-6 col-lg-6 col-xl-4 col-dashboard">@include('dashboard.blocks.community-news')</div>
