@if ($user->number_of_logins <= 2)
    <div class="col-md-12 col-lg-4 col-xl-4 col-dashboard">@include('partials.welcome-materials-host')</div>
    <div class="col-md-12 col-lg-4 col-xl-4 col-dashboard">@include('partials.how-to')</div>
    <div class="col-md-12 col-lg-4 col-xl-4 col-dashboard">@include('dashboard.blocks.discussion')</div>
    <div class="col-lg-12 col-xl-6 col-dashboard">@include('dashboard.blocks.hot-topics')</div>
    <div class="col-md-6 col-lg-6 col-xl-3 col-dashboard">@include('dashboard.blocks.wiki')</div>
    <div class="col-md-6 col-lg-6 col-xl-3 col-dashboard">@include('dashboard.blocks.community-news')</div>
@else
    @if (!empty($inactive_groups))
        <div class="col-lg-3 col-dashboard">@include('partials.create-event')</div>
        <div class="col-lg-6 col-dashboard">@include('partials.past')</div>
        <div class="col-lg-3 col-dashboard">@include('partials.how-to')</div>
    @else
        <div class="col-lg-6 col-xl-6 col-dashboard">@include('partials.past')</div>
        <div class="col-lg-6 col-xl-3 col-dashboard">@include('partials.how-to')</div>
    @endif
    {{-- @if (!empty($all_groups))
        <div class="col-lg-4 col-dashboard">@include('partials.in-your-area')</div>
    @endif--}}
    @if (!empty($outdated_groups))
        <div class="col-lg-6 col-xl-3 col-dashboard">@include('partials.up-to-date')</div>
    @endif
    <div class="col-lg-6 col-xl-3 col-dashboard">@include('dashboard.blocks.discussion')</div>
    <div class="col-lg-6 col-dashboard">@include('dashboard.blocks.hot-topics')</div>
    <div class="col-md-6 col-lg-6 col-xl-3 col-dashboard">@include('dashboard.blocks.wiki')</div>
    <div class="col-md-6 col-lg-6 col-xl-3 col-dashboard">@include('dashboard.blocks.community-news')</div>
@endif
