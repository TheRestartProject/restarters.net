<div class="edit-panel">

    <div class="form-row">
    <div class="col-lg-12">
        <h4>@lang('general.email_alerts')</h4>
        <p>@lang('general.email_alerts_text')</p>
    </div>
    </div>

    <form action="/profile/edit-preferences" method="post">
        @csrf

        {{ Form::hidden('id', $user->id) }}

        <fieldset class="email-options">
            {{-- <div class="form-check d-flex align-items-center justify-content-start">
                @if( $user->newsletter == 1 )
                <input class="checkbox-top form-check-input" type="checkbox" name="newsletter" id="newsletter" value="1" checked>
                @else
                <input class="checkbox-top form-check-input" type="checkbox" name="newsletter" id="newsletter" value="1">
                @endif
                <label class="form-check-label" for="newsletter">
                    @lang('general.email_alerts_pref1')
                </label>
            </div>--}}
            <div class="form-check d-flex align-items-center justify-content-start">
                @if( $user->invites == 1 )
                <input class="checkbox-top form-check-input" type="checkbox" name="invites" id="invites" value="1" checked>
                @else
                <input class="checkbox-top form-check-input" type="checkbox" name="invites" id="invites" value="1">
                @endif
                <label class="form-check-label" for="invites">
                @lang('general.email_alerts_pref2')
            </label>
            </div>
        </fieldset>

        <div class="button-group row">
            <div class="col-sm-9 d-flex align-items-center justify-content-start">
                <a class="btn-preferences" href="{{ env('PLATFORM_COMMUNITY_URL') }}/u/{{ Auth::user()->username }}/preferences/emails">@lang('auth.set_preferences')</a>
            </div>
            <div class="col-sm-3 d-flex align-items-center justify-content-end">
                <button class="btn btn-primary btn-save">@lang('auth.save_preferences')</button>
            </div>
        </div>

    </form>

</div>
