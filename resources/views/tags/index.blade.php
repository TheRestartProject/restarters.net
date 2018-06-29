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

    <div class="row">
        <div class="col-12">
            <div class="table-responsive">

              <table class="table table-hover table-striped bootg" id="tags-table">
                  <thead>
                      <tr>
                          <th data-column-id="tagID"  data-header-css-class="comm-cell" data-identifier="true" data-type="numeric">#</th>
                          <th data-column-id="tag">Tag</th>
                          <th data-column-id="tag-description">Description</th>
                          <th data-column-id="created-at">Created At</th>
                          <th data-column-id="updated-at">Updated At</th>
                          <th data-column-id="edit" data-header-css-class="comm-cell" data-formatter="editLink" data-sortable="false">Actions</th>
                      </tr>
                  </thead>

                  <tbody>
                    @if(isset($tags))
                      @foreach($tags as $tag)
                      <tr>
                        <td><?php echo $tag->id; ?></td>
                        <td><?php echo $tag->tag_name; ?></td>
                        <td><?php echo $tag->description; ?></td>
                        <td><?php echo $tag->created_at; ?></td>
                        <td><?php echo $tag->updated_at; ?></td>
                        <td>
                          <a href="/tags/edit/<?php echo $tag->id; ?>" class="btn btn-warning">edit</a>
                          <a href="/tags/delete/<?php echo $tag->id; ?>" class="btn btn-danger">delete</a>
                        </td>
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
