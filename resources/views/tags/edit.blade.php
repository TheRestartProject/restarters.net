@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ $title }}</h1>
            <hr>
        </div>
    </div>


    <form action="/tags/edit/{{ $tag->id }}" method="post">
      @csrf
        <div class="form-group">
            <label for="tag-name">Tag:</label>
            <input type="text" name="tag-name" id="tag-name" class="form-control" value="{{ $tag->tag_name }}">
        </div>
        <div class="form-group">
            <label for="tag-description">Description:</label>
            <textarea class="form-control rte" rows="6" name="tag-description" id="tag-description">{{ $tag->description }}</textarea>
        </div>
        <div class="form-group">
            <button class="btn btn-default" type="reset"><i class="fa fa-refresh"></i> reset</button>
            <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> save</button>
        </div>
    </form>

</div>

@endsection
