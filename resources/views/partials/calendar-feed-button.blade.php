@if ( Auth::check() )
  @php
    $copy_icon = view('partials.svg-icons.copy-icon');
    $auth = auth()->user();
    $env_hash = env('CALENDAR_HASH');
    $auth_id = $auth->id;
    $read_only_all_events_link = url("/calendar/all-events/{$env_hash}/");
    $content = "<div class='card'>
      <div class='card-body font-family-normal'>
        <p class='font-weight-bold mb-2'>Access all events in your personal calendar</p>
          <p class='mb-2'>Add all your upcoming events to your Google/ Outlook/Yahoo/Apple calendar with the link below:</p>
          <div class='input-group mb-3'>
            <input type='text' class='form-control' readonly value='{$read_only_all_events_link}'>
            <div class='input-group-append'>
              <button class='btn btn-normal-padding btn-primary' id='btn-copy' type='button'>{$copy_icon}</button>
            </div>
          </div>
          <div class='cleafix'>
            <a href='https://talk.restarters.net/t/ical-calendar-feeds/170' class='float-left' target='_blank'>Find out more</a>
            <a href='/profile/edit/{$auth_id}' class='float-right'>See all my calendars</a>
          </div>
      </div>
    </div>";
  @endphp

  <button type="button" class="btn btn-normal-padding btn-sm btn-primary" data-container="body" data-toggle="popover" data-html="true" data-placement="bottom" data-content="{{ $content }}" title="" data-original-title="">
    @include('partials.svg-icons.calendar-icon') <span class="span-vertically-align-middle">Calendar feed</span>
  </button>
@endif
