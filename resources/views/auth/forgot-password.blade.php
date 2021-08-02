@include('layouts.header_plain')
@yield('content')
<section class="login-page">
        <div class="container">
           <div class="align-items-center justify-content-center d-flex">
            @include('includes.logo')
           </div>

            <div class="entry-panel card card__login col-12 mt-5 text-left">

                @if(isset($response))
                  @php( App\Helpers\Fixometer::printResponse($response) )
                @endif

                <h1>@lang('auth.forgotten_pw')</h1>
                <p>@lang('auth.forgotten_pw_text')</p>

                <form class="" method="post" action="/user/recover">
                    @csrf
                    <div class="form-group">
                        <label for="email">@lang('auth.email_address'):</label>
                        <input type="text" class="form-control" id="email" name="email">
                    </div>

                    <div class="row entry-panel__actions">
                        <div class="col-8 align-content-center d-flex">
                            <a class="entry-panel__link" href="/login">@lang('auth.sign_in')</a>
                        </div>
                        <div class="col-4 align-content-center justify-content-end d-flex">
                            <button type="submit" class="btn btn-primary">@lang('auth.reset')</button>
                        </div>
                    </div>


                </form>

            </div><!-- /.entry-panel -->

        </div>
 @include('partials.languages')
    </section>
   
@include('layouts.footer')
