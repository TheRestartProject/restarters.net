@include('layouts/header_plain')
@yield('content')
<section class="features">
    <div class="container">
        @include('includes.info')
        <h2 class="text-center" style="margin:20px 0">@lang('general.introduction_message')</h2>
        <div class="vue">
            <b-carousel
                id="intro-carousel"
                :interval="0"
                controls
                indicators
                img-width="100%"
            >
                @foreach ($slides as $slide)
                <b-carousel-slide
                    img-src="{{ url('/images/slides/' . $slide['image'] . ($agent->isPhone() ? '_m' : '') . '.png') }}"
                    img-alt="{{ $slide['text'] }}"
                ></b-carousel-slide>
                @endforeach
            </b-carousel>
        </div>
        <div class="features__end">
            <a href="{{{ route('registration') }}}" style="box-shadow:none" class="btn btn-primary">@lang('general.signmeup')</a>
            <a href="{{{ route('login') }}}" class="btn btn-secondary">@lang('general.login')</a>
        </div>
    </div>
</section>

@include('partials.languages')
@include('layouts/footer')
