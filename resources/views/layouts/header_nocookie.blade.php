<!doctype html>
<html class="body-plain" lang="{{ app()->getLocale() }}">
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

        @yield('extra-css')

        <!-- Styles -->
        @if( isset($iframe) )
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
        <link href="{{ asset('css/iframe.css') }}" rel="stylesheet">
        @else
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
        @endif
  </head>
<body>