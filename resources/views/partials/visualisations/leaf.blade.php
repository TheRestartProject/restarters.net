<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('global/css/app.css') }}" rel="stylesheet">
{{--    <script src="https://unpkg.com/vue@2.7.16"></script>--}}
{{--    <script src="https://unpkg.com/vue3-sfc-loader/dist/vue2-sfc-loader.js"></script>--}}
{{--    <script src="https://unpkg.com/lang.js@1.1.14"></script>--}}
</head>
<body>
<div class="vue">
    <StatsShare :count="{{{ $co2 }}}" :target="'Facebook'"/>
</div>
<script>
    window.noCookieNotice = true
    {{--const {loadModule, vueVersion} = window['vue2-sfc-loader'];--}}

    {{--const options = {--}}
    {{--    moduleCache: {--}}
    {{--        vue: Vue,--}}
    {{--        myData: {--}}
    {{--            vueVersion,--}}
    {{--        }--}}
    {{--    },--}}
    {{--    getFile(url) {--}}
    {{--        return fetch(url).then(response => response.ok ? response.text() : Promise.reject(response));--}}
    {{--    },--}}
    {{--    addStyle() { /* unused here */--}}
    {{--    },--}}
    {{--}--}}

    {{--loadModule('/js/translations.js', options).then(translations => {--}}
    {{--    loadModule('/js/components/StatsShare.vue', options).then(component => {--}}
    {{--        console.log('Component', component)--}}

    {{--        const lang = new Lang({--}}
    {{--            locale: 'en',--}}
    {{--            fallback: 'en',--}}
    {{--            messages: translations--}}
    {{--        })--}}
    {{--        component.computed.$lang = () => lang--}}

    {{--        new Vue(component).$mount('#app')--}}

    {{--        console.log('Set vals')--}}
    {{--        component.methods.setCount(Number({{{ $co2 }}}))--}}
    {{--        component.methods.setTarget(String('Facebook'))--}}
    {{--    })--}}
    {{--})--}}
</script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
