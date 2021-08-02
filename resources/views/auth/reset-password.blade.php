@include('layouts.header_plain')
@yield('content')
<section class="entry-wrapper align-items-center justify-content-center">
        <div class="container">

            @include('includes.logo')

            @if($valid_code == false)
              <div class="entry-panel card card__login col-12 mt-5 text-left">
                <h1>@lang('auth.reset_password')</h1>
                <p class="login-text">The recovery code you're using is invalid. Please proceed to request a new recovery link <a href="/user/recover">here</a>.</p>
              </div>
            @else

            <div class="entry-panel card card__login col-12 mt-5 text-left">

                @if(isset($response))
                  @php( App\Helpers\Fixometer::printResponse($response) )
                @endif

                <h1>@lang('auth.reset_password')</h1>

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
                        <div class="col-12 justify-content-end d-flex">
                            <button type="submit" name="submit" id="submit" class="btn btn-primary">@lang('auth.change_password')</button>
                        </div>
                    </div>

                </form>

            </div><!-- /.entry-panel -->

            @endif

        </div>
    </section>
    @include('layouts.footer')
