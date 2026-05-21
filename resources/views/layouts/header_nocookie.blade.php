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

        <!-- Styles -->
        @vite(['resources/sass/app.scss', 'resources/global/css/app.scss'])
        @if( isset($iframe) )
        <link href="{{ asset('css/iframe.css') }}" rel="stylesheet">
        @endif
  </head>
<body>