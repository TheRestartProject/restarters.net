@php
  $copy_icon = view('partials.svg-icons.copy-icon');
  $auth_id = auth()->id();
  $content = "<p class='font-weight-bold'>Access all events in your personal calendar</p>
              <p>Add all your upcoming events to your Google/ Outlook/Yahoo/Apple calendar with the link below:</p>
              <div class='input-group mb-3'>
                <input type='text' class='form-control' value='https://restarters.net/calendar/all-events/gGc0WX4YFpfbukT1/'>
                <div class='input-group-append'>
                  <button class='btn btn-normal-padding btn-primary' type='button'>{$copy_icon}</button>
                </div>
              </div>
              <div class='cleafix'>
                <a href='https://talk.restarters.net/t/ical-calendar-feeds/170' class='float-left'>Find out more</a>
                <a href='/profile/edit/{$auth_id}' class='float-right'>See all my calendars</a>
                ";
@endphp



<button type="button" class="btn btn-normal-padding btn-sm btn-primary" data-container="body" data-toggle="popover" data-html="true" data-placement="bottom" data-content="{{ $content }}" title="" data-original-title="">
  @include('partials.svg-icons.calendar-icon') <span class="span-vertically-align-middle">Calendar feed</span>
</button>
