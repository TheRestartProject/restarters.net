<div class="col-sm-3">
    <aside class="dashboard__aside">
        <ul class="steps">
            <li class="step step__active">
                <h3>@lang('dashboard.getting_started_header')</h3>
                <p>@lang('dashboard.getting_started_text')</p>
            </li>

            @if (FixometerHelper::hasRole($user, 'Restarter'))
            <li class="step">
                <div class="row">
                    <div class="col-7 d-flex align-items-center">
                        <h4>@lang('dashboard.join_group_restarter')</h4>
                    </div>
                    @if ($in_group)
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                    </div>
                    @else
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <a href="{{{ route('groups') }}}" class="step__link">@lang('dashboard.join_group_link_restarter')</a>
                    </div>
                    @endif
                </div>
            </li>
            <li class="step">
                <div class="row">
                    <div class="col-7 d-flex align-items-center">
                        <h4>@lang('dashboard.rsvp_event')</h4>
                    </div>
                    @if ($in_event)
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <svg width="20" height="17" class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                    </div>
                    @else
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <a href="{{{ route('events') }}}" class="step__link">Find an event</a>
                    </div>
                    @endif
                </div>
            </li>
            @endif

            @if (FixometerHelper::hasRole($user, 'Host'))
            <li class="step">
                <div class="row">
                    <div class="col-7 d-flex align-items-center">
                        <h4>@lang('dashboard.join_group_host')</h4>
                    </div>
                    @if ($in_group)
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                    </div>
                    @else
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <a href="{{ route('groups') }}" class="step__link">@lang('dashboard.join_group_link_host')</a>
                    </div>
                    @endif
                </div>
            </li>
                @if ($in_group)
                <li class="step">
                    <div class="row">
                        <div class="col-7 d-flex align-items-center">
                            <h4>Host an event</h4>
                        </div>
                        @if ($in_event)
                        <div class="col-5 d-flex align-items-center justify-content-end">
                            <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                        </div>
                        @else
                        <div class="col-5 d-flex align-items-center justify-content-end">
                            <a href="{{ route('events') }}" class="step__link">Create an event</a>
                        </div>
                        @endif
                    </div>
                </li>
                @endif
            @endif
            <li class="step">
                <div class="row">
                    <div class="col-7 d-flex align-items-center">
                        <h4>@lang('dashboard.join_discussion')</h4>
                    </div>
                    @if ($user_exists_in_discourse)
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                    </div>
                    @else
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <a href="{{ env('DISCOURSE_URL') }}/login" class="step__link">Join</a>
                    </div>
                    @endif
                </div>
            </li>
            <li class="step">
                <div class="row">
                    <div class="col-7 d-flex align-items-center">
                        <h4>@lang('dashboard.add_skills')</h4>
                    </div>
                    @if ($has_skills)
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                    </div>
                    @else
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <a href="/profile/edit/{{{ Auth::user()->id }}}#repair-skills" class="step__link">Add Skills</a>
                    </div>
                    @endif
                </div>
            </li>
            <li class="step">
                <div class="row">
                    <div class="col-7 d-flex align-items-center">
                        <h4>@lang('dashboard.add_avatar')</h4>
                    </div>
                    @if ($has_profile_pic)
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <svg class="step__tick" viewBox="0 0 21 17" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Tick</title><g fill="#78ca6e"><path d="M7.646 16.486l3.823-3.823-7.646-7.646L0 8.84l7.646 7.646z"/><path d="M20.309 3.823L16.486 0 3.823 12.663l3.823 3.823L20.309 3.823z"/></g></svg>
                    </div>
                    @else
                    <div class="col-5 d-flex align-items-center justify-content-end">
                        <a href="/profile/edit/{{{ Auth::user()->id }}}#change-photo" class="step__link">Upload photo</a>
                    </div>
                    @endif
                </div>
            </li>
        </ul>
    </aside>
    {{-- @if ($show_fixfest_cta)
    <aside>
        @include('dashboard.blocks.fixfest')
    </aside>
    @endif
    <br/>
    <aside>
        @include('dashboard.blocks.repairday')
    </aside>
    --}}
</div>
