@if (!empty($inactive_groups))
  <div class="col-lg-6 col-dashboard">@include('partials.create-event')</div>
@endif
<div class="col-lg-6 col-dashboard">@include('partials.past')</div>
@if ($user->number_of_logins == 2)
  <div class="col-lg-4 col-dashboard">@include('partials.how-to')</div>
@endif
@if (!empty($all_groups))
  <div class="col-lg-4 col-dashboard">@include('partials.in-your-area')</div>
@endif
@if (!empty($outdated_groups))
  <div class="col-lg-4 col-dashboard">@include('partials.up-to-date')</div>
@endif
<div class="col-lg-4 col-dashboard">@include('partials.wiki')</div>
<div class="col-lg-4 col-dashboard">@include('partials.discussion')</div>
