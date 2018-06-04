@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ $title }}</h1>
            <hr>
        </div>
    </div>


    <form action="/skills/edit/{{ $skill->id }}" method="post">
      @csrf
        <div class="form-group">
            <label for="skill-name">Skill:</label>
            <input type="text" name="skill-name" id="skill-name" class="form-control" value="{{ $skill->skill_name }}">
        </div>
        <div class="form-group">
            <label for="skill-description">Description:</label>
            <textarea class="form-control rte" rows="6" name="skill-description" id="skill-description">{{ $skill->description }}</textarea>
        </div>
        <div class="form-group">
            <button class="btn btn-default" type="reset"><i class="fa fa-refresh"></i> reset</button>
            <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> save</button>
        </div>
    </form>

</div>

@endsection
