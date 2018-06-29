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
              <li class="breadcrumb-item"><a href="{{ route('brands') }}">@lang('admin.brand')</a></li>
              <li class="breadcrumb-item active" aria-current="page">@lang('admin.edit-brand')</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>

    <div class="row">
        <div class="col-lg-6">

            <div class="edit-panel edit-panel__device">

                <h2>@lang('admin.edit-brand')</h2>
                <p>@lang('admin.edit-brand-content')</p>

                <form action="/brands/edit/{{ $brand->id }}" method="post">
                  @csrf
                    <div class="form-group">
                        <label for="brand-name">@lang('admin.brand-name'):</label>
                        <input type="text" name="brand-name" id="brand-name" class="form-control" value="{{ $brand->brand_name }}">
                    </div>
                    <div class="button-group row">
                      <div class="col-lg-12 d-flex align-items-center justify-content-end">
                        <button class="btn btn-default" type="reset"><i class="fa fa-refresh"></i>Reset</button>
                        <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i>@lang('admin.save-brand')</button>
                      </div>
                    </div>
                </form>

            </div><!-- /edit-panel -->
        </div>
    </div>
  </div>
</section>

@endsection
