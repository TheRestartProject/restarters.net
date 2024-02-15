<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('global/css/app.css') }}" rel="stylesheet">
</head>
<body>
<div class="vue">
    <StatsShare :count="{{{ $co2 }}}" :target="'Facebook'"/>
</div>
<script>
    // We don't want the cookie notice in the IFRAME.
    window.noCookieNotice = true
</script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
