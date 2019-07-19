<div id="calendar-feed" class="d-none">
  <div class="card">
    <div class="card-body font-family-normal">
      <p class="font-weight-bold mb-2">{{ $modal_title }}</p>
      <p class="mb-2">{{ $modal_text }}</p>
      <div class="input-group mb-3">
        <input type="text" class="form-control" value="{{ $copy_link }}">
        <div class="input-group-append">
          <button class="btn btn-primary btn-normal-padding" id="btn-copy" type="button">
            @include('partials.svg-icons.copy-icon')
          </button>
        </div>
      </div>
      <div class="cleafix">
          <a href="https://talk.restarters.net/t/fixometer-how-to-add-repair-events-to-your-calendar-application/1770" class="float-left" target="_blank">@lang('calendars.find_out_more')</a>
        <a href="{{ $user_edit_link }}#list-calendar-links" class="float-right">@lang('calendars.see_all_calendars')</a>
      </div>
    </div>
  </div>
</div>

<button type="button" class="btn btn-normal-padding btn-sm btn-primary mx-2 btn-calendar-feed">
  @include('partials.svg-icons.calendar-icon') <span class="span-vertically-align-middle">@lang('calendars.add_to_calendar')</span>
</button>
