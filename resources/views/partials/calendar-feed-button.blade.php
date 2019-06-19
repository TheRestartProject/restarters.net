@if ( Auth::check() )
  @php
    $user = auth()->user();
    $all_events_link = url("/calendar/user/{$user->calendar_hash}");
    $calendar_events_link = url("/profile/edit/{$user->id}");
  @endphp

  <div id="calendar-feed" class="d-none">
    <div class="card">
      <div class="card-body font-family-normal">
        <p class="font-weight-bold mb-2">Access all events in your personal calendar</p>
        <p class="mb-2">Add all your upcoming events to your Google/ Outlook/Yahoo/Apple calendar with the link below:</p>
        <div class="input-group mb-3">
          <input type="text" class="form-control" value="{{ $all_events_link }}">
          <div class="input-group-append">
            <button class="btn btn-primary btn-normal-padding" id="btn-copy" type="button">
              @include('partials.svg-icons.copy-icon')
            </button>
          </div>
        </div>
        <div class="cleafix">
          <a href="https://talk.restarters.net/t/ical-calendar-feeds/170" class="float-left" target="_blank">Find out more</a>
          <a href="{{ $calendar_events_link }}" class="float-right">See all my calendars</a>
        </div>
      </div>
    </div>
  </div>

  <button type="button" class="btn btn-normal-padding btn-sm btn-primary btn-calendar-feed">
    @include('partials.svg-icons.calendar-icon') <span class="span-vertically-align-middle">Calendar feed</span>
  </button>

@endif
