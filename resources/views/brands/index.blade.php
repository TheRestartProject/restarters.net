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
                            <th>Brand name</th>
                        </tr>
                    </thead>

                    <tbody>
                      @if(isset($brands))
                        @foreach($brands as $brand)
                        <tr>
                          <td><a href="/brands/edit/{{{ $brand->id }}}">{{{ $brand->brand_name }}}</td>
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
