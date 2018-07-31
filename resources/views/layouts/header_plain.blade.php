<!doctype html>
<html class="body-plain" lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Styles -->
        @if( isset($iframe) )
          <link href="{{ asset('css/iframe.css') }}" rel="stylesheet">
        @else
          <link href="{{ asset('css/app.css') }}" rel="stylesheet">
        @endif

        @include('includes/gmap')

        <script src="{{ asset('js/gdpr-cookie-notice.js') }}"></script>
        <script>
         window.restarters = {};
         restarters.cookie_domain = '{{ env('SESSION_DOMAIN') }}';
         var gdprCookiesCheck = Cookies;
         var gdprCurrentCookiesSelection = gdprCookiesCheck.getJSON('gdprcookienotice');
         restarters.analyticsCookieEnabled = gdprCurrentCookiesSelection['analytics'];
        </script>
    </head>

    @if ( isset($onboarding) && $onboarding )
      <body class="onboarding">
    @else
      <body>
    @endif
