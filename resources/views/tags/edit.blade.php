@extends('layouts.app')

@section('content')
<section class="admin">
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="d-flex justify-content-between align-content-center">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">FIXOMETER</a></li>
              <li class="breadcrumb-item"><a href="{{ route('tags') }}">@lang('admin.group-tags')</a></li>
              <li class="breadcrumb-item active" aria-current="page">@lang('admin.edit-tag')</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>

    <div class="edit-panel edit-panel__device">
         <h2>@lang('admin.edit-group-tag')</h2>

        <div class="row">
            <div class="col-lg-4">
                <p>@lang('admin.edit-group-tag-content')</p>
            </div>
        </div>

        <form action="/tags/edit/{{ $tag->id }}" method="post">
          @csrf

          <div class="row">
              <div class="col-lg-4">

                  <div class="form-group">
                      <label for="tag-name">@lang('admin.tag-name'):</label>
                      <input type="text" name="tag-name" id="tag-name" class="form-control" value="{{ $tag->tag_name }}">
                  </div>

              </div>
              <div class="offset-lg-1 col-lg-7">

                  <div class="form-group">
                      <label for="tag-description">@lang('admin.description'):</label>
                      <textarea class="form-control rte" rows="6" name="tag-description" id="tag-description">{{ $tag->description }}</textarea>
                  </div>

              </div>
          </div>
        </form>

        <div class="button-group row">
            <div class="col-lg-6 d-flex align-items-center justify-content-start">
                <button type="submit" class="btn btn-primary btn-danger">@lang('admin.delete-tag')</button>
            </div>
            <div class="col-lg-6 d-flex align-items-center justify-content-end">
                <button type="submit" class="btn btn-primary btn-create">@lang('admin.save-tag')</button>
            </div>
        </div>

    </div><!-- /edit-panel -->


  </div>
</section>

@endsection
