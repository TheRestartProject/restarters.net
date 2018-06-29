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
              <li class="breadcrumb-item active" aria-current="page">@lang('admin.brand')</li>
            </ol>
          </nav>

          <div class="btn-group">
            <button data-toggle="modal" data-target="#add-new-brand" class="btn btn-primary btn-save">@lang('admin.create-new-brand')</button>
          </div>

        </div>
      </div>
    </div>

    <br>

    <div class="row">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-hover table-striped bootg" id="brands-table">
                    <thead>
                        <tr>
                            <th data-column-id="brandID"  data-header-css-class="comm-cell" data-identifier="true" data-type="numeric">#</th>
                            <th data-column-id="brand">Brand name</th>
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
    </div>


  </div>
</section>
@include('includes/modals/create-brand')
@endsection
