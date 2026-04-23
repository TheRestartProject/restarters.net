<!doctype html>
<html class="body-plain" lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @yield('extra-meta')
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />

        <title>
            @hasSection('title')
            @yield('title')
            @else
            {{ config('app.name', 'Laravel') }}
            @endif
        </title>

        @yield('extra-css')

        @if( !isset($iframe) || !$iframe )
        <!-- Load jQuery first to ensure it's available for all scripts -->
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.4.1/dist/jquery.min.js"></script>

        <!-- Load jQuery plugins immediately after jQuery -->
        <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/ekko-lightbox@5.3.0/dist/ekko-lightbox.min.js"></script>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-fQybjgWLrvvRgtW6bFlB7jaZrFsaBXjsOMm/tB9LTS58ONXgqbR9W8oWht/amnpF" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/js-cookie@2.2.1/src/js.cookie.min.js"></script>

        <!-- Leaflet CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        @endif

        <!-- Styles -->
        @vite(['resources/sass/app.scss', 'resources/global/css/app.scss'])
        @if( isset($iframe) )
          <link href="{{ asset('css/iframe.css') }}" rel="stylesheet">
        @endif

        <!-- Meta tags for social previews. -->
        <meta data-hid="og:type" property="og:type" content="website">
        <meta data-hid="description" name="description" content="{{ __('landing.intro') }}">
        <meta data-hid="og:image" property="og:image" content="{{ url('/images/landing/landing1.jpg') }}">
        <meta data-hid="og:locale" property="og:locale" content="en_GB">
        <meta data-hid="og:title" property="og:title" content="{{ config('app.name', 'Laravel') }}">
        <meta data-hid="og:site_name" property="og:site_name" content="{{ config('app.name', 'Laravel') }}">
        <meta data-hid="og:url" property="og:url" content="{{ url()->current() }}">
        <meta data-hid="og:description" property="og:description" content="{{ __('landing.intro') }}">
        <meta data-hid="twitter:title" name="twitter:title" content="{{ config('app.name', 'Laravel') }}">
        <meta data-hid="twitter:description" name="twitter:description" content="{{ __('landing.intro') }}">
        <meta data-hid="twitter:image" name="twitter:image" content="{{ url('/images/landing/landing1.jpg') }}">
        <meta data-hid="twitter:image:alt" name="twitter:image:alt" content="The Restart logo">
        <meta data-hid="twitter:card" name="twitter:card" content="summary_large_image">
        <meta data-hid="twitter:site" name="twitter:site" content="RestartProject">

        @if( !isset($iframe) || !$iframe )
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

        @if (isset($show_login_join_to_anons) && $show_login_join_to_anons)
            <div class="container container-nav">
                <nav class="navbar navbar-expand-md navbar-light">
                    <div class="d-none d-sm-block navbar-brand">
                        @include('includes/logo')
                    </div>
                    <div class="d-block d-sm-none">
                        @include('includes/logo-plain')
                    </div>

                <div id="navbarSupportedContent" class="collapse navbar-collapse">
                    <ul class="navbar-nav ml-auto">
                        <li><a class="nav-link" href="/login">@lang('login.login_title')</a></li>
                        <li><a class="nav-link" href="/user/register">@lang('login.join_title')</a></li>
                    </ul>
                </div>

                </nav>
            </div>
        @endif
