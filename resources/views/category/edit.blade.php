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
              <li class="breadcrumb-item"><a href="{{ route('category') }}">@lang('admin.categories')</a></li>
              <li class="breadcrumb-item active" aria-current="page">@lang('admin.edit-category')</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>

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

    <div class="edit-panel edit-panel__device">
         <h2>@lang('admin.edit-category')</h2>

        <div class="row">
            <div class="col-lg-4">
                <!-- <p>@lang('admin.edit-category-content')</p> -->
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4">
              <form action="/category/edit/{{ $category->idcategories }}" method="post">
                @csrf
                <div class="form-group">
                    <label for="category_name">@lang('admin.category_name'):</label>
                    <input type="text" id="category_name" name="category_name" class="field form-control" value="{{ $category->name }}">
                </div>
                <div class="form-group">
                    <label for="weight">@lang('admin.weight'):</label>
                    <input type="number" id="weight" name="weight" class="field form-control" value="{{ $category->weight }}">
                </div>
                <div class="form-group">
                    <label for="co2_footprint">@lang('admin.co2_footprint'):</label>
                    <input type="number" id="co2_footprint" name="co2_footprint" class="field form-control" value="{{ $category->footprint }}">
                </div>
                <div class="form-group">
                    <label for="reliability">@lang('admin.reliability'):</label>
                    <select name="reliability" id="reliability" class="form-control">
                      @foreach(App\Helpers\Fixometer::footprintReliability() as $key => $value)
                        <option value="{{ $key }}" {{ $key == $category->footprint_reliability ? 'selected' : ''}} >{{ $value }}</option>
                      @endforeach
                    </select>
                </div>
                <?php //dd($categories);?>
                <div class="form-group">
                    <label for="category_cluster">@lang('admin.category_cluster'):</label>
                    <select name="category_cluster" id="category_cluster" class="form-control">
                      <!-- REDUNDANT   -->
                      <!-- @foreach(App\Helpers\Fixometer::categoryCluster() as $key => $value)
                        <option value="{{ $key }}" {{ $key == $category->cluster ? 'selected' : ''}} >{{ $value }}</option>
                      @endforeach -->

                      @if(isset($categories))
                        <?php foreach ($categories as $cluster) { ?>
                        <option value="<?php echo $cluster->idclusters; ?>"<?php echo $cluster->idclusters == $category->cluster ? ' selected' : ''; ?>><?php echo $cluster->name; ?></option>
                        <?php } ?>
                      @endif
                    </select>
                </div>



            </div>
            <div class="offset-lg-1 col-lg-7">

                <div class="form-group">
                    <label for="categories_desc">@lang('admin.description'):</label>
                    <textarea name="categories_desc" id="categories_desc" class="form-control field textarea-large">{{ $category->description_short }}</textarea>
                </div>
                <div class="button-group row">
                    <div class="col-lg-12 d-flex align-items-center justify-content-end">
                        <button type="submit" class="btn btn-primary btn-create">@lang('admin.save-category')</button>
                    </div>
                </div>
              </form>

            </div>
        </div>

    </div><!-- /edit-panel -->


  </div>
</section>

@endsection
