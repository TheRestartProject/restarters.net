@extends('layouts.app')

@section('content')
<div class="vue-placeholder vue-placeholder-large">
    <div class="vue-placeholder-content">@lang('partials.loading')...</div>
</div>
<div class="vue">
    <CategoriesPage
        :initial-categories="{{ json_encode($categoriesForVue) }}"
        :initial-edit-id="{{ json_encode($editId) }}"
        :clusters="{{ json_encode($clusters) }}"
        :reliability-options="{{ json_encode($reliabilityOptions) }}"
        api-token="{{ $apiToken }}"
    />
</div>
@endsection
