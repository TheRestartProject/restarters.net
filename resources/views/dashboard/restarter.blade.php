{{-- @if ($user->number_of_logins <= 2)
    <div class="col-lg-3 col-dashboard">@include('dashboard.blocks.discussion')</div>
    <div class="col-lg-6 col-dashboard">
        <div class="mb-4">
        @include('partials.upcoming2')
        </div>
        @include('partials.past')
    </div>
    <div class="col-lg-3 col-dashboard">@include('dashboard.blocks.welcome-materials-restarter')</div>
    <div class="col-lg-4 col-dashboard">@include('dashboard.blocks.hot-topics')</div>
    <div class="col-lg-2 col-dashboard">@include('dashboard.blocks.wiki')</div>
    <div class="col-lg-2 col-dashboard">@include('dashboard.blocks.community-news')</div>
@else --}}
    <div class="col-md-6 col-lg-6 col-xl-3 col-dashboard">@include('dashboard.blocks.discussion')</div>
    <div class="col-md-6 col-lg-6 col-xl-3 col-dashboard">@include('dashboard.blocks.welcome-materials-restarter')</div>
    <div class="col-md-12 col-lg-12 col-xl-6 col-dashboard">
        <div class="mb-4">
        @include('partials.upcoming2')
        </div>
        @include('partials.past')
    </div>
    <div class="col-lg-12 col-xl-6 col-dashboard">@include('dashboard.blocks.hot-topics')</div>
    <div class="col-md-6 col-lg-6 col-xl-3 col-dashboard">@include('dashboard.blocks.wiki')</div>
    <div class="col-md-6 col-lg-6 col-xl-3 col-dashboard">@include('dashboard.blocks.community-news')</div>
{{-- @endif --}}
