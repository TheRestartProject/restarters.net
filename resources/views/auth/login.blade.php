@include('layouts.header_plain')
@yield('content')
<section class="login-page">
    <div class="container">

        @include('includes.info')

        <div class="row row-expanded pb-3">
            <div class="col-lg-6 d-flex">

                <form action="{{ route('login') }}" method="post" class="card card__login col-12 panel">

                    @if (\Session::has('success'))
                        <div class="alert alert-success">
                            {!! \Session::get('success') !!}
                        </div>
                    @endif

                    @csrf

                    {!! Honeypot::generate('my_name', 'my_time') !!}

                    <legend>@lang('login.login_title')</legend>

                    <div class="form-group">
                        <label for="fp_email">@lang('auth.email_address'):</label>
                        <input type="email" name="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" id="fp_email" value="{{ old('email') }}" required autofocus>

                        @if ($errors->has('email'))
                            <span class="invalid-feedback">
                                <strong>{{ $errors->first('email') }}</strong>
                            </span>
                        @endif

                    </div>

                    <div class="form-group">
                        <label for="password">@lang('auth.password'):</label>
                        <input type="password" name="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" id="password" required>

                        @if ($errors->has('password'))
                            <span class="invalid-feedback">
                                <strong>{{ $errors->first('password') }}</strong>
                            </span>
                        @endif
                    </div>


                    <div class="row entry-panel__actions">
                        <div class="col-6 col-md-8 align-content-center flex-column d-flex">
                            <div class="row">
                                <div class="col-12">
                                    <a class="entry-panel__link" href="/user/recover">@lang('auth.forgot_password')</a>
                                </div>
                                <div class="col-12">
                                    <a class="entry-panel__link" href="{{{ route('registration') }}}">@lang('auth.create_account')</a>
                                </div>
                            </div>


                        </div>
                        <div class="col-6 col-md-4 align-content-center flex-column justify-content-end d-flex">
                            <button type="submit" class="btn btn-primary">@lang('auth.login')</button>
                        </div>
                    </div>


                </form>

            </div>
            <div class="col-lg-6">

                <div class="card card__content col-12 panel panel__orange">
                    <h3 style="font-weight:700">@lang('login.whatis')</h3>
                    @lang('login.whatis_content')

                    <a href="/about" class="card__link">@lang('login.more')</a>
                </div>

            </div>
        </div>

    </div>
    @include('partials.languages')
</section>
@include('layouts.footer')
