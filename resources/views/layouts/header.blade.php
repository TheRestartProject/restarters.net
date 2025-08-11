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
    @vite(['resources/sass/app.scss', 'resources/global/css/app.scss'])

    @yield('extra-css')

    <!-- Cookie banner with fine-grained opt-in -->
    <script src="{{ asset('js/gdpr-cookie-notice.js') }}"></script>
    <!-- Check to see if visitor has opted in to analytics cookies -->
    <script>
        window.restarters = {};
        restarters.cookie_domain = '{{ env('SESSION_DOMAIN') }}';
        var gdprCookiesCheck = Cookies;
        var gdprCurrentCookiesSelection = gdprCookiesCheck.getJSON('gdprcookienotice');
        restarters.analyticsCookieEnabled = (typeof gdprCurrentCookiesSelection !== 'undefined' && gdprCurrentCookiesSelection['analytics']);
    </script>

    <!-- Matomo -->
    <script>
      var _paq = window._paq = window._paq || [];
      /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
      _paq.push(['trackPageView']);
      _paq.push(['enableLinkTracking']);
      (function() {
        var u="https://restartproject.matomo.cloud/";
        _paq.push(['setTrackerUrl', u+'matomo.php']);
        _paq.push(['setSiteId', '1']);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.async=true; g.src='//cdn.matomo.cloud/restartproject.matomo.cloud/matomo.js'; s.parentNode.insertBefore(g,s);
      })();
    </script>
    <!-- End Matomo Code -->
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
