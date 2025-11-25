@include('layouts.header_plain')
@yield('content')
<section class="registration">
    <div class="container">

        @include('includes.info')

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{!! $error !!}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (\Session::has('auth-for-invitation'))
            <div class="alert alert-info">
                {!! \Session::get('auth-for-invitation') !!}
            </div>
        @endif

        <form id="register-form" method="post">
          <input type="hidden" name="_token" value="{{ csrf_token() }}">
        @csrf

        {!! Honeypot::generate('my_name', 'my_time') !!}

        @if(isset($event_id) && isset($invite))
          <input name="event" type="hidden" value="{{ $event_id }}">
          <input name="invite" type="hidden" value="{{ $invite }}">
        @endif

        <aside class="panel registration__step registration__step--active" id="step-1" aria-labelledby="step-1-form-label">
            <h3 id="step-1-form-label"> @lang('registration.reg-step-1-heading')</h3>
            <p class="registration__status">@lang('registration.step-1')</p>

            <legend id="step-1-form-label">@lang('registration.reg-step-1-1')</legend>
            @foreach( App\Helpers\Fixometer::skillCategories() as $key => $skill_category )
              <br>
              <h5>@lang($skill_category)</h5>
              <div class="row row-compressed">
                  @foreach ($skills[$key] as $skill)
                    <div class="col-6 col-lg-3">
                        <input @if( is_array(old('skills')) && in_array($skill->id, old('skills')) ) checked @endif type="checkbox" name="skills[]" id="skill-{{ $skill->id }}" class="styled-checkbox" value="{{ $skill->id }}">
                        <label for="skill-{{ $skill->id }}" class="btn btn-checkbox"><span>@lang($skill->skill_name)</span></label>
                    </div>
                  @endforeach
              </div>
            @endforeach
            <div class="button-group d-flex justify-content-end">
                <button data-target="step-2" class="btn btn-primary btn-next" aria-expanded="false" aria-controls="step-2">@lang('registration.next-step')</button>
            </div>

        </aside>

        <aside class="panel registration__step" id="step-2" aria-labelledby="step-2-form-label">

            <h3 id="step-2-form-label">@lang('registration.reg-step-2-heading')</h3>
            <p class="registration__status">@lang('registration.step-2')</p>

            <fieldset class="registration__offset">

                <div class="error"><span></span></div>
                <div class="fieldset">
                    <legend>@lang('registration.reg-step-2-1')</legend>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="name">@lang('general.your_name'):<sup>*</sup></label>
                                @if( Auth::check() )
                                  <input type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }} field" id="registerName" name="name" value="{{{ Auth::user()->name }}}" disabled aria-required="true">
                                @else
                                  <input type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }} field" id="registerName" name="name" value="{{{ old('name') }}}" required aria-required="true">
                                @endif
                                <div class="invalid-feedback">@lang('general.your_name_validation')</div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group emailtest">
                                <label for="email">@lang('auth.email_address'):<sup>*</sup></label>
                                @if( Auth::check() )
                                  <input type="email" class="form-control field is-valid" id="registeremail" name="email" value="{{{ Auth::user()->email }}}" disabled aria-required="true">
                                  <div class="invalid-feedback">
                                    Please choose a username.
                                  </div>
                                @else
                                  <input type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }} field" id="registeremail" name="email" value="{{{ old('email') }}}" required aria-required="true">
                                @endif
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="age">@lang('registration.age'):<sup>*</sup></label>
                                <select id="age" name="age" required aria-required="true" class="form-control">
                                    @foreach(App\Helpers\Fixometer::allAges() as $age)
                                      <option @if( old('age') == $age ) selected @endif value="{{ $age }}">{{ $age }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">@lang('registration.age_validation')</div>
                                <small id="age_help" class="form-text text-muted">@lang('registration.age_help')</small>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="country">@lang('registration.country'):<sup>*</sup></label>
                                <select id="country" name="country" required aria-required="true" class="form-control">
                                    <option value=""></option>
                                    <?php $countries = App\Helpers\Fixometer::getAllCountries(); asort($countries); ?>
                                    @foreach ($countries as $country_code => $country_name)
                                      <option value="{{ $country_code }}" @if( old('country') == $country_code ) selected @endif>{{ $country_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">@lang('registration.country_validation')</div>
                                <small id="country_help" class="form-text text-muted">@lang('registration.country_help')</small>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="gender">@lang('registration.gender'):</label>
                                <input type="text" class="form-control field" id="gender" name="gender" value="{{{ old('gender') }}}">
                                <small id="gender_help" class="form-text text-muted">@lang('registration.gender_help')</small>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="city">@lang('registration.town-city'):</label>
                                <input type="text" class="form-control" id="city" name="city" placeholder="@lang('registration.town-city-placeholder')" value="{{{ old('city') }}}">
                                <small id="city_help" class="form-text text-muted">@lang('registration.town-city_help')</small>
                            </div>
                        </div>
                    </div>

                </div>

                @if( !Auth::check() )
                  <div class="fieldset">
                      <legend>@lang('registration.reg-step-2-2')</legend>

                      <div class="row">
                          <div class="col-lg-6">
                              <div class="form-group">
                                  <label for="password">@lang('auth.password'):<sup>*</sup></label>
                                  <input type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }} field" id="password" name="password" value="{{{ old('password') }}}" required aria-required="true">
                                  <div class="email-invalid-feedback invalid-feedback">@lang('auth.repeat_password_validation')</div>
                              </div>
                          </div>
                          <div class="col-lg-6">
                              <div class="form-group">
                                  <label for="password-confirm">@lang('auth.repeat_password'):<sup>*</sup></label>
                                  <input type="password" class="form-control field" id="password-confirm" name="password_confirmation" value="{{{ old('password_confirmation') }}}" required aria-required="true">
                                  <div class="email-invalid-feedback invalid-feedback">@lang('auth.repeat_password_validation')</div>
                              </div>
                          </div>
                      </div>
                  </div>
                @endif

            </fieldset><!-- /registration__offset -->

            <div class="button-group row">
                <div class="col-6 d-flex align-items-center justify-content-start">
                    <button class="registration__prev" data-target="step-1" aria-expanded="false" aria-controls="step-1">@lang('registration.previous-step')</button>
                </div>
                <div class="col-6 d-flex align-items-center justify-content-end">
                    <button type="submit" data-target="step-3" class="btn btn-primary btn-next" aria-expanded="false" aria-controls="step-3">@lang('registration.next-step')</button>
                </div>
            </div>

        </aside>

        <aside class="panel registration__step" id="step-3" aria-labelledby="step-3-form-label">

            <h3 id="step-3-form-label">@lang('registration.reg-step-3-heading')</h3>
            <p class="registration__status">@lang('registration.step-3')</p>

            <div class="registration__offset">
                <fieldset>
                    <legend @if(! $showNewsletterSignup) class="d-none" @endif>
                        @lang('registration.reg-step-3-2b')
                    </legend>
                    <legend>@lang('registration.reg-step-3-1a')</legend>
                    <div class="form-check align-items-center justify-content-start @if($showNewsletterSignup) d-flex @else d-none @endif">
                        <input class="form-check-input" type="checkbox" name="newsletter" id="newsletter" value="1" @if( old('newsletter') == 1 ) checked @endif>
                        <label class="form-check-label" for="newsletter">
                            @lang('registration.reg-step-3-label1')
                        </label>
                    </div>
                    <div class="form-check d-flex align-items-center justify-content-start">
                        <input class="form-check-input" type="checkbox" name="invites" id="invites" value="1" @if( old('invites') == 1 ) checked @endif>
                        <label class="form-check-label" for="invites">
                        @lang('registration.reg-step-3-label2')
                    </label>
                    </div>
                </fieldset>
            </div><!-- /registration__offset -->

            <div class="button-group row">
                <div class="col-6 d-flex align-items-center justify-content-start">
                    <button data-target="step-2" class="registration__prev" aria-expanded="false" aria-controls="step-2">@lang('registration.previous-step')</button>
                </div>
                <div class="col-6 d-flex align-items-center justify-content-end">
                    <button data-target="step-4" class="btn btn-primary btn-next" aria-expanded="false" aria-controls="step-4">@lang('registration.next-step')</button>
                </div>
            </div>

        </aside>

        <aside class="panel registration__step" id="step-4" aria-labelledby="step-4-form-label">

            <h3 id="step-4-form-label">@lang('registration.reg-step-4-heading')</h3>
            <p class="registration__status">@lang('registration.step-4')</p>

            <fieldset>
                <legend>@lang('registration.reg-step-4')</legend>
                <div class="form-check d-flex align-items-top justify-content-start">
                    <input class="checkbox-top form-check-input" type="checkbox" name="consent_gdpr" id="consent_gdpr" value="1" @if( old('consent_gdpr') == 1 ) checked @endif>
                    <label class="form-check-label" for="consent_gdpr">
                      @lang('registration.reg-step-4-label1')
                    </label>
                </div>
                <div class="form-check d-flex align-items-top justify-content-start">
                    <input class="checkbox-top form-check-input" type="checkbox" name="consent_future_data" id="consent_future_data" value="1" @if( old('consent_future_data') == 1 ) checked @endif>
                    <label class="form-check-label" for="consent_future_data">
                      @lang('registration.reg-step-4-label2')
                    </label>
                </div>
                @if( Auth::check() )
                <div class="form-check d-flex align-items-top justify-content-start">
                    <input type="hidden" name="consent_past_data" id="consent_past_data" value="1" />
                    <!-- <input class="checkbox-top form-check-input" type="checkbox" name="consent_past_data" id="consent_past_data" value="1" @if( old('consent_past_data') == 1 ) checked @endif>
                         <label class="form-check-label" for="consent_past_data">
                         @lang('registration.reg-step-4-label3')
                         </label> -->
                </div>
                @endif
            </fieldset>

            <div class="button-group row">
                <div class="col-6 d-flex align-items-center justify-content-start">
                    <button data-target="step-3" class="registration__prev" aria-expanded="false" aria-controls="step-3">@lang('registration.previous-step')</button>
                </div>
                <div class="col-6 d-flex align-items-center justify-content-end">
                    <input data-target="" id="register-form-submit" type="submit" value="@lang('registration.complete-profile')" class="btn btn-primary"/>
                </div>
            </div>

        </aside>


    </div>
</section>

@include('partials.languages')
@include('layouts.footer')
