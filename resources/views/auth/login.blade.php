@include('layouts.header_plain')
@yield('content')
<section class="login-page">
    <div class="container">

        @include('includes.info')

        @if (\Session::has('success'))
            {{-- This is used by password reset. --}}
            <div class="alert alert-success">
                {!! \Session::get('success') !!}
            </div>
        @endif

        <div class="vue">
            <LoginPage
                csrf="{{ csrf_token() }}"
                :error="{{ count($errors->all()) ? 'true' : 'false' }}"
                time="{{ Crypt::encrypt(time()) }}"
                email="{{ old('email') }}"
            />
        </div>

    </div>
    @include('partials.languages')
</section>
@include('layouts.footer')
