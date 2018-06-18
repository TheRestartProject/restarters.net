@include('layouts.header_plain')
@yield('content')
<section class="entry-wrapper align-items-center justify-content-center">
        <div class="container">

            @include('includes.logo-large')

            @if($valid_code == false)
            <br>
            <p class="login-text text-center">The recovery code you're using is invalid. Please proceed to request a new recovery link <a href="/user/recover">here</a>.</p>
            @else

            <div class="entry-panel">

                @if(isset($response))
                  @php( FixometerHelper::printResponse($response) )
                @endif

                <h1>@lang('auth.reset_password')</h1>
                <p>@lang('auth.reset_password_text')</p>

                <form class="" method="post" action="/user/reset?recovery=<?php echo $recovery; ?>">
                    @csrf
                    <input type="hidden" name="recovery" value="<?php echo $recovery; ?>">

                    <div class="form-group">
                        <label for="fp_email">@lang('auth.email_address'):</label>
                        <input type="email" class="form-control" id="fp_email" value="{{ $email }}" disabled>
                    </div>

                    <div class="form-group">
                        <label for="password">@lang('auth.password'):</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Your new password...">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">@lang('auth.repeat_password'):</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your new password..." >
                    </div>

                    <div class="row entry-panel__actions">
                        <div class="col-6 align-content-center d-flex">

                        </div>
                        <div class="col-6 align-content-center justify-content-end d-flex">
                            <button type="submit" name="submit" id="submit" class="form-control btn btn-primary login-button">@lang('auth.change_password')</button>
                        </div>
                    </div>

                </form>

            </div><!-- /.entry-panel -->

            @endif

        </div>
    </section>
    @include('layouts.footer')
