@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>
            <hr>
        </div>
    </div>


    <form action="/brands/create" method="post">
      @csrf
        <div class="form-group">
            <label for="brand-name">Brand:</label>
            <input type="text" name="brand-name" id="brand-name" class="form-control">
        </div>
        <div class="form-group">
            <button class="btn btn-default" type="reset"><i class="fa fa-refresh"></i> reset</button>
            <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> save</button>
        </div>
    </form>

</div>

@endsection
