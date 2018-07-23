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
              <li class="breadcrumb-item active" aria-current="page">@lang('admin.group-tags')</li>
            </ol>
          </nav>

          <div class="btn-group">
            <button data-toggle="modal" data-target="#add-new-tag" class="btn btn-primary btn-save">@lang('admin.create-new-tag')</button>
          </div>

        </div>
      </div>
    </div>

    <br>

    @if (\Session::has('success'))
        <div class="alert alert-success">
            {!! \Session::get('success') !!}
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="table-responsive">

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
                        <td>{{{ str_limit(strip_tags($tag->description), 150, '...') }}}</td>
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
