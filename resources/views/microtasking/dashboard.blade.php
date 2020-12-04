@extends('layouts.app', ['show_login_join_to_anons' => true])

@section('title')
    @lang('microtasking.title')
@endsection

@if( Auth::guest() )
{{-- Adding this is here in order to apply global styles. --}}
{{-- However not blanketly adding it to header_plain, as then that messes up the login/register pages for some reason. --}}
@section('extra-css')
<link href="{{ asset('global/css/app.css') }}" rel="stylesheet">
@endsection
@endif

@section('content')
<section class="microtasking">
    <div class="container">

        <div class="vue-placeholder vue-placeholder-large">
            <div class="vue-placeholder-content">@lang('partials.loading')...</div>
        </div>

        <div class="vue">
            <MicrotaskingPage
              :total-contributions="{{ $totalContributions }}"
              :current-user-quests="{{ $currentUserQuests }}"
              :current-user-contributions="{{ $currentUserContributions }}"
              :topics="{{ json_encode($topics) }}"
              see-all-topics-link="{{ $seeAllTopicsLink }}"
              :is-logged-in="{{ Auth::check() ? 'true' : 'false'  }}"
              discourse-base-url="{{ env('DISCOURSE_URL') }}"
            />
        </div>
    </div>
</section>
@endsection
