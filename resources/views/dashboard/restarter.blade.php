@if ($user->number_of_logins <= 2)
    <div class="col-lg-3 col-dashboard">@include('partials.discussion')</div>
    <div class="col-lg-3 col-dashboard">@include('partials.welcome-materials-restarter')</div>
    <div class="col-lg-6 col-dashboard">@include('partials.upcoming2')</div>
    <div class="col-lg-3 col-dashboard">@include('partials.wiki')</div>
    <div class="col-lg-3 col-dashboard">@include('partials.community-news')</div>
@else
    <div class="col-lg-3 col-dashboard">@include('partials.discussion')</div>
    <div class="col-lg-6 col-dashboard">@include('partials.upcoming2')</div>
    <div class="col-lg-3 col-dashboard">@include('partials.welcome-materials-restarter')</div>
    <div class="col-lg-6 col-dashboard">@include('partials.past')</div>
    <div class="col-lg-3 col-dashboard">@include('partials.wiki')</div>
    <div class="col-lg-3 col-dashboard">@include('partials.community-news')</div>
@endif
