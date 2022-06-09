@extends('layouts.app', ['show_navbar_to_anons' => false, 'show_login_join_to_anons' => true, 'hide_language' => true])

@section('title')
Style Guide
@endsection

@section('content')

<style>
    .hide {
        display: none;
    }

    .panel-foo {
        border: 1px dotted #555;
        padding: 5px;
        margin: 5px 0;
    }

    pre {
        font-size: x-small;
    }
</style>

<section>

    <div class="container">
    <div>
    <pre>
        {{ $content }}
    </pre>
    </div>

    </div>

</section>

@endsection

@section('scripts')
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {


    }, false);
</script>

@endsection