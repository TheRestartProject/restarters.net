@include('layouts.header_plain')
@yield('content')
<section class="registration">
    <div class="container">

        @include('includes.info')

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="register-form" action="/user/register" method="post">

        @csrf

        {!! Honeypot::generate('my_name', 'my_time') !!}

        <aside class="registration__step registration__step--active" id="step-1" aria-labelledby="step-1-form-label">
            <h3> @lang('registration.reg-step-1-heading')</h3>
            <p class="registration__status">@lang('registration.step-1')</p>
            <!-- <form id="step-1-form"> -->
                <legend id="step-1-form-label">@lang('registration.reg-step-1-1'):</legend>
                <br>
                <div class="row row-compressed">
                    @foreach ($skills as $skill)
                      <div class="col-sm-6 col-lg-3">
                          <input type="checkbox" name="skills[]" id="skill-{{ $skill->id }}" class="styled-checkbox" value="{{ $skill->id }}">
                          <label for="skill-{{ $skill->id }}" class="btn btn-checkbox"><span>{{ $skill->skill_name }}</span></label>
                      </div>
                    @endforeach
                </div>
                <div class="button-group d-flex justify-content-end">
                    <button data-target="step-2" class="btn btn-primary btn-next" aria-expanded="false" aria-controls="step-2">@lang('registration.next-step')</button>
                </div>
            <!-- </form> -->
        </aside>

        <aside class="registration__step" id="step-2" aria-labelledby="step-2-form-label">

            <h3 id="step-2-form-label">@lang('registration.reg-step-2-heading')</h3>
            <p class="registration__status">@lang('registration.step-2')</p>

            <!-- <form id="step-2-form" action="#" method="POST"> -->
                <fieldset class="registration__offset">

                    <div class="error"><span></span></div>
                    <div class="fieldset">
                        <legend>@lang('registration.reg-step-2-1'):</legend>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="reg_name">@lang('general.your_name'):<sup>*</sup></label>
                                    <input type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }} field" id="reg_name" name="reg_name" required aria-required="true">
                                    <div class="invalid-feedback">@lang('general.your_name_validation')</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="reg_email">@lang('auth.email_address'):<sup>*</sup></label>
                                    <input type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }} field" id="reg_email" name="email" required aria-required="true">
                                    <div class="invalid-feedback">@lang('auth.email_address_validation')</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="reg_age">@lang('registration.age'):<sup>*</sup></label>
                                    <div class="form-control form-control__select">
                                        <select id="reg_age" name="reg_age" required aria-required="true" class="field">
                                            @foreach(FixometerHelper::allAges() as $age)
                                              <option value="{{ $age }}">{{ $age }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="invalid-feedback">@lang('registration.age_validation')</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="reg_country">@lang('registration.country'):<sup>*</sup></label>
                                    <div class="form-control form-control__select">
                                        <select id="reg_country" name="reg_country" required aria-required="true" class="field">
                                            <option value=""></option>
                                            @foreach (FixometerHelper::getAllCountries() as $key => $value)
                                              <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="invalid-feedback">@lang('registration.country_validation')</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="reg_gender">@lang('registration.gender'):<sup>*</sup></label>
                                    <input type="text" class="form-control field" id="reg_gender" name="reg_gender" required aria-required="true">
                                    <div class="invalid-feedback">@lang('registration.gender_validation')</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="reg_city">@lang('registration.town-city'):</label>
                                    <input type="text" class="form-control" id="reg_city" name="reg_city" placeholder="@lang('registration.town-city-placeholder')">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="fieldset">
                        <legend>@lang('registration.reg-step-2-2'):</legend>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="password">@lang('auth.password'):<sup>*</sup></label>
                                    <input type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }} field" id="password" name="password" required aria-required="true">
                                    <div class="invalid-feedback">@lang('auth.repeat_password_validation')</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="password-confirm">@lang('auth.repeat_password'):<sup>*</sup></label>
                                    <input type="password" class="form-control field" id="password-confirm" name="password_confirmation" required aria-required="true">
                                    <div class="invalid-feedback">@lang('auth.repeat_password_validation')</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </fieldset><!-- /registration__offset -->

                <div class="button-group row">
                    <div class="col-6 d-flex align-items-center justify-content-start">
                        <button class="registration__prev" data-target="step-1" aria-expanded="false" aria-controls="step-1">@lang('registration.previous-step')</button>
                    </div>
                    <div class="col-6 d-flex align-items-center justify-content-end">
                        <button type="submit" data-target="step-3" class="btn btn-primary btn-next" aria-expanded="false" aria-controls="step-3">@lang('registration.next-step')</button>
                    </div>
                </div>


            <!-- </form> -->
        </aside>

        <aside class="registration__step" id="step-3" aria-labelledby="step-3-form-label">

            <h3 id="step-3-form-label">@lang('registration.reg-step-3-heading')</h3>
            <p class="registration__status">@lang('registration.step-3')</p>

            <!-- <form id="step-3-form"> -->
                <div class="registration__offset">
                    <fieldset>
                        <legend>@lang('registration.reg-step-3-1a') <span id="email-update">example@mail.com</span> @lang('registration.reg-step-3-1b'):</legend>
                        <div class="form-check d-flex align-items-center justify-content-start">
                            <input class="form-check-input" type="checkbox" name="newsletter" id="newsletter" value="1">
                            <label class="form-check-label" for="newsletter">
                            @lang('registration.reg-step-3-label1')
                        </label>
                        </div>
                        <div class="form-check d-flex align-items-center justify-content-start">
                            <input class="form-check-input" type="checkbox" name="invites" id="invites" value="1">
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


            <!-- </form> -->
        </aside>

        <aside class="registration__step" id="step-4" aria-labelledby="step-4-form-label">

            <h3 id="step-4-form-label">@lang('registration.reg-step-4-heading')</h3>
            <p class="registration__status">@lang('registration.step-4')</p>

            <!-- <form id="step-4-form"> -->
                <fieldset>
                    <div class="form-check d-flex align-items-top justify-content-start">
                        <input class="checkbox-top form-check-input" type="checkbox" name="consent" id="consent" value="1">
                        <label class="form-check-label" for="consent">
                        @lang('registration.reg-step-4-label1')
                    </label>
                    </div>
                    <div class="form-check d-flex align-items-top justify-content-start">
                        <input class="checkbox-top form-check-input" type="checkbox" name="consent2" id="consent2" value="1">
                        <label class="form-check-label" for="consent2">
                        @lang('registration.reg-step-4-label2')
                    </label>
                    </div>
                </fieldset>

                <div class="button-group row">
                    <div class="col-6 d-flex align-items-center justify-content-start">
                        <button data-target="step-3" class="registration__prev" aria-expanded="false" aria-controls="step-3">@lang('registration.previous-step')</button>
                    </div>
                    <div class="col-6 d-flex align-items-center justify-content-end">
                        <input data-target="" id="register-form-submit" type="submit" value="@lang('registration.complete-profile')" class="btn btn-primary"/>
                    </div>
                </div>

            <!-- </form> -->


        </aside>


    </div>
</section>
@include('layouts.footer')
