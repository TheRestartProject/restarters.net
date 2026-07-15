@extends('layouts.app')

@section('content')
<div class="vue-placeholder vue-placeholder-large">
    <div class="vue-placeholder-content">@lang('partials.loading')...</div>
</div>
<div class="vue">
    <GroupTagsPage
        :initial-tags="{{ json_encode($tagsForVue) }}"
        :initial-edit-id="{{ json_encode($editId) }}"
        api-token="{{ $apiToken }}"
    />
</div>
@endsection
