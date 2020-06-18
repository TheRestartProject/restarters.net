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
                      Brands
                  </h1>

            <button data-toggle="modal" data-target="#add-new-brand" class="btn btn-primary btn-save ml-auto">@lang('admin.create-new-brand')</button>

              </div>
        </div>
      </div>

    <br>

    <div class="row">
        <div class="col-12">
            <div class="table-responsive table-section">
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
