@include('layouts/header_plain')
@yield('content')
<section class="features">
    <div class="container">
        <a class="features__link" href="{{{ route('login') }}}">@lang('general.register-signin')</a>
        <header>
            <a href="{{{ route('login') }}}">
              @include('includes/logo-large')
            </a>
            <p>@lang('general.introduction_message')</p>
        </header>
        <div class="slideshow">
            <div><img src="{{ url('/images/slides/0-onboard-overview.png') }}" alt=""></div>
            <div><img src="{{ url('/images/slides/1-onboard-discussion.png') }}" alt="Learn how to organise and volunteer at events"><h2 class="featured-slide__heading">Learn how to organise and volunteer at events</h2></div>
            <div><img src="{{ url('/images/slides/2-onboard-technicalhelp.jpg') }}" alt="Get technical help, on tools, safety and risk"><h2 class="featured-slide__heading">Get technical help, on tools, safety and risk</h2></div>
            <div><img src="{{ url('/images/slides/3-onboard-manage-event.png') }}" alt="Announce an event, find people"><h2 class="featured-slide__heading">Announce an event, find people</h2></div>
            <div><img src="{{ url('/images/slides/4-onboard-event.jpg') }}" alt="Host an event and share skills"><h2 class="featured-slide__heading">Host an event and share skills</h2></div>
            <div><img src="{{ url('/images/slides/5-onboard-add-devices.png') }}" alt="Log the repairs, to reveal impact and help future repairers"><h2 class="featured-slide__heading">Log the repairs, to reveal impact and help future repairers</h2></div>
            <div><img src="{{ url('/images/slides/6-onboard-barriers-to-repair.png') }}" alt="Bring down the barriers to repair "><h2 class="featured-slide__heading">Bring down the barriers to repair</h2></div>
        </div>
        <div class="features__end">
            <a href="{{{ route('register') }}}" class="btn btn-primary">@lang('general.signmeup')</a>
        </div>
    </div>
</section>
@include('layouts/footer')
