@include('layouts/header_plain')
@yield('content')
<section class="features">
    <div class="container">
        @include('includes.info')
        <h2 class="text-center" style="margin:20px 0">@lang('general.introduction_message')</h2>
        <div class="slideshow">
            @foreach ($slides as $slide)
                <div><img src="{{ url('/images/slides/' . $slide['image'] . ($agent->isPhone() ? '_m' : '') . '.png') }}" alt="{{ $slide['text'] }}"></div>
            @endforeach
        </div>
        <div class="features__end">
            <a href="{{{ route('registration') }}}" style="box-shadow:none" class="btn btn-primary">@lang('general.signmeup')</a>
            <a href="{{{ route('login') }}}" class="btn btn-secondary">@lang('general.login')</a>
        </div>
    </div>
</section>

@include('partials.languages')
@include('layouts/footer')
