@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>
            <a class="btn btn-default btn-sm" href="/brands/create"><i class="fa fa-plus"></i>New Brand</a>
            <hr>
        </div>
    </div>

    <div class="col-md-12">

        <table class="table table-hover table-responsive table-striped bootg" id="brands-table">
            <thead>
                <tr>
                    <th data-column-id="brandID"  data-header-css-class="comm-cell" data-identifier="true" data-type="numeric">#</th>
                    <th data-column-id="brand">Brand</th>
                    <th data-column-id="created-at">Created At</th>
                    <th data-column-id="updated-at">Updated At</th>
                    <th data-column-id="edit" data-header-css-class="comm-cell" data-formatter="editLink" data-sortable="false">Actions</th>
                </tr>
            </thead>

            <tbody>
              @if(isset($brands))
                @foreach($brands as $brand)
                <tr>
                  <td><?php echo $brand->id; ?></td>
                  <td><?php echo $brand->brand_name; ?></td>
                  <td><?php echo $brand->created_at; ?></td>
                  <td><?php echo $brand->updated_at; ?></td>
                  <td>
                    <a href="/brands/edit/<?php echo $brand->id; ?>" class="btn btn-warning">edit</a>
                    <a href="/brands/delete/<?php echo $brand->id; ?>" class="btn btn-danger">delete</a>
                  </td>
                </tr>
                @endforeach
              @endif
            </tbody>
        </table>

    </div>
</div>

@endsection
