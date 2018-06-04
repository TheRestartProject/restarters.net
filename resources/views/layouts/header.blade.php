<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    @if(isset($gmaps) && $gmaps == true)
      <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDb1_XdeHbwLg-5Rr3EOHgutZfqaRp8THE&v=3.exp&signed_in=true"></script>
    @endif
    <!-- <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDb1_XdeHbwLg-5Rr3EOHgutZfqaRp8THE&callback=initMap"
    type="text/javascript"></script> -->

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
