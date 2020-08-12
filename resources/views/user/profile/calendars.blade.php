<div class="edit-panel">
    <div class="form-row">
    <div class="col-lg-12">
        <h3>Calendars</h3>
        <p>You can now keep track of events using your personal calendar application by subscribing to the calendar feeds below. You can subscribe to as many calendars as you like. <a href="{{ env('DISCOURSE_URL' )}}/session/sso?return_path={{ env('DISCOURSE_URL') }}@lang('general.calendar_feed_help_url')">Find out more</a>.</p>
    </div>
    </div>
    <fieldset class="listed-calendar-links">
    <h5 class="mb-3">My events</h5>

    <div class="input-group mb-4">
        <input type="text" class="form-control" value="{{ url('/calendar/user/'.auth()->user()->calendar_hash) }}">
        <div class="input-group-append">
        <button class="btn btn-normal-padding btn-primary btn-copy-input-text" type="button">Copy link</button>
        </div>
    </div>

    <h5 class="mb-3">Group calendars</h5>

    @foreach ($groups as $group)
        <p class="mb-2">{{ $group->name }}</p>
        <div class="input-group mb-4">
        <input type="text" class="form-control" value="{{ url("/calendar/group/{$group->idgroups}") }}">
        <div class="input-group-append">
            <button class="btn btn-normal-padding btn-primary btn-copy-input-text" type="button">Copy link</button>
        </div>
        </div>
    @endforeach

    @if (FixometerHelper::hasRole(Auth::user(), 'Administrator'))
        <h5 class="mb-3">@include('partials.svg-icons.admin-cog-icon') <span class="span-vertically-align-middle">All events (admin only)</span></h5>

        <div class="input-group mb-4">
        @php( $env_hash = env('CALENDAR_HASH') )
        <input type="text" class="form-control" value="{{ url("/calendar/all-events/{$env_hash}/") }}">
        <div class="input-group-append">
            <button class="btn btn-normal-padding btn-primary btn-copy-input-text" type="button">Copy link</button>
        </div>
        </div>
    @endif

    <h5 class="mb-3">Events by area</h5>

    <div class="input-group input-group-select2 mb-3">
        <select class="form-control select2-with-input-group" id="inputGroupSelect02">
        @foreach ($all_group_areas as $area)
            @if($loop->first)
            @php( $first_option = $area )
            @endif
            <option value="{{ $area }}">{{ $area }}</option>
        @endforeach
        </select>
        <input type="text" class="form-control" value="{{ url("/calendar/group-area/{$first_option}") }}">
        <div class="input-group-append">
        <button class="btn btn-normal-padding btn-primary btn-copy-input-text" type="button">Copy link</button>
        </div>
    </div>

    </fieldset>
</div>
