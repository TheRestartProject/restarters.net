<div class="edit-panel">
    <div class="form-row">
    <div class="col-lg-12">
        <h3>@lang('profile.calendars.title')</h3>
        <p>@lang('profile.calendars.explainer') <a href="{{ env('DISCOURSE_URL' )}}/session/sso?return_path={{ env('DISCOURSE_URL') }}@lang('general.calendar_feed_help_url')">@lang('profile.calendars.find_out_more')</a>.</p>
    </div>
    </div>
    <fieldset class="listed-calendar-links">
    <h5 class="mb-3">@lang('profile.calendars.my_events')</h5>

    <div class="input-group mb-4">
        <input type="text" class="form-control" value="{{ url('/calendar/user/'.auth()->user()->calendar_hash) }}">
        <div class="input-group-append">
        <button class="btn btn-normal-padding btn-primary btn-copy-input-text" type="button">@lang('profile.calendars.copy_link')</button>
        </div>
    </div>

    <h5 class="mb-3">@lang('profile.calendars.group_calendars')</h5>

    @foreach ($groups as $group)
        <p class="mb-2">{{ $group->name }}</p>
        <div class="input-group mb-4">
        <input type="text" class="form-control" value="{{ url("/calendar/group/{$group->idgroups}") }}">
        <div class="input-group-append">
            <button class="btn btn-normal-padding btn-primary btn-copy-input-text" type="button">@lang('profile.calendars.copy_link')</button>
        </div>
        </div>
    @endforeach

    @if (App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator'))
        <h5 class="mb-3">@include('partials.svg-icons.admin-cog-icon') <span class="span-vertically-align-middle">@lang('profile.calendars.all_events')</span></h5>

        <div class="input-group mb-4">
        @php( $env_hash = env('CALENDAR_HASH') )
        <input type="text" class="form-control" value="{{ url("/calendar/all-events/{$env_hash}/") }}">
        <div class="input-group-append">
            <button class="btn btn-normal-padding btn-primary btn-copy-input-text" type="button">@lang('profile.calendars.copy_link')</button>
        </div>
        </div>
    @endif

    <h5 class="mb-3">@lang('profile.calendars.events_by_area')</h5>

    <div class="input-group mb-3">
        <select class="form-control" id="inputGroupSelect02">
        @php($first_option=null)
        @foreach ($all_group_areas as $area)
            @if(!$first_option)
            @php( $first_option = $area )
            @endif
            <option value="{{ $area }}">{{ $area }}</option>
        @endforeach
        </select>
        @if (!is_null($first_option))
        <input type="text" class="form-control" value="{{ url("/calendar/group-area/{$first_option}") }}">
        @endif
        <div class="input-group-append">
        <button class="btn btn-normal-padding btn-primary btn-copy-input-text" type="button">@lang('profile.calendars.copy_link')</button>
        </div>
    </div>

    </fieldset>
</div>
