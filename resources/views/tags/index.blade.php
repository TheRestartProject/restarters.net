@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>
            <a class="btn btn-default btn-sm" href="/tags/create"><i class="fa fa-plus"></i>New Tag</a>
            <hr>
        </div>
    </div>

    <div class="col-md-12">

        <table class="table table-hover table-responsive table-striped bootg" id="tags-table">
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

@endsection
