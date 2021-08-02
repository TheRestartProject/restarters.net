@extends('layouts.app')

@section('content')
<section class="admin">
    <div class="container">
        @if (\Session::has('success'))
        <div class="alert alert-success">
            {!! \Session::get('success') !!}
        </div>
        @endif

        @if (\Session::has('danger'))
        <div class="alert alert-danger">
            {!! \Session::get('danger') !!}
        </div>
        @endif


        <div class="row mb-30">
            <div class="col-12 col-md-12">
                <div class="d-flex align-items-center">
                    <h1 class="mb-0 mr-30">
                        @lang('admin.group-tags')
                    </h1>

                    <button data-toggle="modal" data-target="#add-new-tag" class="btn btn-primary btn-save ml-auto">@lang('admin.create-new-tag')</button>

                </div>
            </div>
        </div>

        <br>

        <div class="row">
            <div class="col-12">
                <div class="table-responsive table-section">

                    <table class="table table-hover table-striped bootg" id="tags-table">
                        <thead>
                            <tr>
                                <th>Tag name</th>
                                <th>Description</th>
                            </tr>
                        </thead>

                        <tbody>
                            @if(isset($tags))
                            @foreach($tags as $tag)
                            <tr>
                                <td><a href="/tags/edit/{{{ $tag->id }}}">{{{ $tag->tag_name }}}</a></td>
                                <td>{{{ Str::limit(strip_tags($tag->description), 150, '...') }}}</td>
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>

                </div>
            </div>
        </div>


    </div>
</section>

@include('includes/modals/create-tag')
@endsection
