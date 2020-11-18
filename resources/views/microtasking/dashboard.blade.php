@extends('layouts.app', ['show_login_join_to_anons' => true])

@section('title')
    @lang('microtasking.microtasking')
@endsection

@section('content')
<section class="groups">
    <div class="container">
        <div class="row mb-30">
            <div class="col-12 col-md-12">
                <div class="d-flex align-items-center">
                    <h1 class="mb-0 mr-30">
                        @lang('microtasking.microtasking')
                    </h1>

                    <div class="mr-auto d-none d-md-block">
                        @include('svgs.group.group-doodle')
                    </div>

                </div>
            </div>
        </div>


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
