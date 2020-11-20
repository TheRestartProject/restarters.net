@extends('layouts.app', ['show_login_join_to_anons' => true])

@section('title')
    @lang('microtasking.microtasking')
@endsection

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
            />
        </div>
    </div>
</section>
@endsection
