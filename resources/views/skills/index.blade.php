@extends('layouts.app')

@section('content')
<div class="vue-placeholder vue-placeholder-large">
    <div class="vue-placeholder-content">@lang('partials.loading')...</div>
</div>
<div class="vue">
    <SkillsPage
        :initial-skills="{{ json_encode($skillsForVue) }}"
        :initial-edit-id="{{ json_encode($editId) }}"
        :skill-categories="{{ json_encode($skillCategories) }}"
        api-token="{{ $apiToken }}"
    />
</div>
@endsection
