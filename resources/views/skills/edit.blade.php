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
              <li class="breadcrumb-item"><a href="{{ route('skills') }}">@lang('admin.skills')</a></li>
              <li class="breadcrumb-item active" aria-current="page">@lang('admin.edit-skill')</li>
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
    @if (\Session::has('warning'))
        <div class="alert alert-warning">
            {!! \Session::get('warning') !!}
        </div>
    @endif

    <div class="edit-panel edit-panel__device">
         <h2>@lang('admin.edit-skill')</h2>

        <div class="row">
            <div class="col-lg-4">
                <p>@lang('admin.edit-skill-content')</p>
            </div>
        </div>

        <form action="/skills/edit/{{ $skill->id }}" method="post">
          @csrf
            <div class="form-group">
                <label for="skill-name">Skill:</label>
                <input type="text" name="skill-name" id="skill-name" class="form-control" value="{{ $skill->skill_name }}">
            </div>
            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" class="form-control" data-live-search="true">
                    <option></option>
                    @foreach( App\Helpers\Fixometer::skillCategories() as $key => $category )
                      @if( $skill->category == $key )
                        <option selected value="{{{ $key }}}">{{{ $category }}}</option>
                      @else
                        <option value="{{{ $key }}}">{{{ $category }}}</option>
                      @endif
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="skill-description">Description:</label>
                <textarea class="form-control" rows="6" name="skill-description" id="skill-description">{{ $skill->description }}</textarea>
            </div>

            <div class="button-group row">
                <div class="col-6 d-flex align-items-center justify-content-start">
                    <a href="/skills/delete/{{ $skill->id }}" class="btn btn-primary btn-danger">@lang('admin.delete-skill')</a>
                </div>
                <div class="col-6 d-flex align-items-center justify-content-end">
                    <button type="submit" class="btn btn-primary btn-create">@lang('admin.save-skill')</button>
                </div>
            </div>

        </form>

    </div><!-- /edit-panel -->

  </div>
</section>
@endsection
