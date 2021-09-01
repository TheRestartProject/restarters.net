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
                      Editing {{ $tag->tag_name }} tag
                  </h1>
              </div>
          </div>
      </div>

    <div class="edit-panel">

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
                      <textarea class="form-control" rows="6" name="tag-description" id="tag-description">{{ $tag->description }}</textarea>
                  </div>

              </div>
          </div>

        <div class="button-group row">
            <div class="col-6 d-flex align-items-center justify-content-start">
                <a href="/tags/delete/{{ $tag->id }}" class="btn btn-primary btn-danger">@lang('admin.delete-tag')</a>
            </div>
            <div class="col-6 d-flex align-items-center justify-content-end">
                <button type="submit" class="btn btn-primary btn-create">@lang('admin.save-tag')</button>
            </div>
        </div>
        </form>

    </div><!-- /edit-panel -->


  </div>
</section>

@endsection
