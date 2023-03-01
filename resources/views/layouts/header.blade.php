<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @yield('extra-meta')
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @hasSection('title')
        @yield('title')
        @else
        {{ config('app.name', 'Laravel') }}
        @endif
    </title>

    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('global/css/app.css') }}" rel="stylesheet">

    @yield('extra-css')

    <!-- Cookie banner with fine-grained opt-in -->
    <script src="{{ asset('js/gdpr-cookie-notice.js') }}"></script>
    <!-- Check to see if visitor has opted in to analytics cookies -->
    <script>
        window.restarters = {};
        restarters.cookie_domain = '{{ env('PLATFORM_ENVIRONMENT') . '-' . env('PLATFORM_PROJECT') . '.uk-1.platformsh.site' }}';
        var gdprCookiesCheck = Cookies;
        var gdprCurrentCookiesSelection = gdprCookiesCheck.getJSON('gdprcookienotice');
        restarters.analyticsCookieEnabled = (typeof gdprCurrentCookiesSelection !== 'undefined' && gdprCurrentCookiesSelection['analytics']);
    </script>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    @if( !empty(env('GOOGLE_ANALYTICS_TRACKING_ID')) )
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_ANALYTICS_TRACKING_ID') }}"></script>
        <script>
        if (restarters.analyticsCookieEnabled) {
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '{{ env('GOOGLE_ANALYTICS_TRACKING_ID') }}');
          <!-- Google Tag Manager -->
          (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0], j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src= 'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
          })(window,document,'script','dataLayer', '{{ env('GOOGLE_TAG_MANAGER_ID') }}');
          <!-- End Google Tag Manager -->

          <!-- Analytics to allow ga.send for custom events -->
          (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                                   m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
          })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

          ga('create', '{{ env('GOOGLE_ANALYTICS_TRACKING_ID') }}', 'auto');
        }
        </script>
    @endif
  </head>
  @if( Request::is('login') || Request::is('user/register') )
    <body class="fixed-layout">
  @elseif ( isset($onboarding) && $onboarding )
    <body class="onboarding">
  @else
    <body>
  @endif
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ env('GOOGLE_TAG_MANAGER_ID') }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    @include('layouts.navbar')
