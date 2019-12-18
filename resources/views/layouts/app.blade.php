@if( Auth::guest() )
  @include('layouts/header_plain')
@else
  @include('layouts/header')
@endif
@yield('content')

<!-- Modal -->
<div class="modal fade" id="onboarding" tabindex="-1" role="dialog" aria-labelledby="onboardingLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <svg width="21" height="21" viewBox="0 0 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M21,19.389l-19.389,-19.389l-1.611,1.611l19.389,19.389l1.611,-1.611Z"/><path d="M1.611,21l19.389,-19.389l-1.611,-1.611l-19.389,19.389l1.611,1.611Z"/></g></svg>
      </button>

      <div class="modal-slideshow">
        <div>
            <article>
                <img src="/images/onboarding/onboarding_1.jpg" class="rounded-circle img-fluid" alt="Two Restarters attempting a fix" width="250">
                <h1>@lang('onboarding.slide1_heading')</h1>
                @lang('onboarding.slide1_content')
            </article>
        </div>

        <div>
            <article>
                <img src="/images/onboarding/onboarding_2.jpg" class="rounded-circle img-fluid" alt="A volunteer helping a member of the public with their phone" width="250">
                <h1>@lang('onboarding.slide2_heading')</h1>
                @lang('onboarding.slide2_content')
            </article>
        </div>

        <div>
            <article>
                <img src="/images/onboarding/onboarding_3.jpg" class="rounded-circle img-fluid" alt="A Restarter fixing a device at one of our events" width="250">
                <h1>@lang('onboarding.slide3_heading')</h1>
                @lang('onboarding.slide3_content')
            </article>
        </div>
      </div><!-- /slideshow -->

      <button class="modal-prev"><svg width="9" height="14" viewBox="0 0 7 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M0,5.303l5.303,5.304l1.22,-1.22l-5.304,-5.303l-1.219,1.219Z"/><path d="M5.303,0l-5.303,5.303l1.219,1.22l5.304,-5.304l-1.22,-1.219Z"/></g></svg> @lang('onboarding.previous')</button>
      <button class="modal-next">@lang('onboarding.next') <svg width="9" height="14" viewBox="0 0 7 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M6.523,5.303l-5.304,5.304l-1.219,-1.22l5.303,-5.303l1.22,1.219Z"/><path d="M1.219,0l5.304,5.303l-1.22,1.22l-5.303,-5.304l1.219,-1.219Z"/></g></svg></button>
      <a href="#" class="btn btn-primary modal-finished" data-dismiss="modal" aria-label="Close">@lang('onboarding.finishing_action')</a>

    </div>
  </div>
</div>

{{-- @if( Auth::check() )
@include('partials.languages')
@endif
--}}
@include('layouts/footer')
