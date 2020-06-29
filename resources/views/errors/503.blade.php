@if (Auth::check())
    @include('layouts.header')
@else
    @include('layouts.header_plain')
@endif

@yield('content')

<section class="errors">
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-12 align-self-center">

                <div class="panel">
                    <h1>Scheduled maintenance.</h1>

                    <!--<img class="rounded img-fluid" src="/images/broken-toaster.png" alt="Woman with broken toaster" />-->

                    <h2>
                        We are currently undertaking some scheduled maintenance.  We'll be back shortly!
                    </h2>
                </div>

            </div>
        </div>
    </div>

</section>

@include('layouts.footer')
