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
              <li class="breadcrumb-item active" aria-current="page">@lang('admin.skills')</li>
            </ol>
          </nav>

          <div class="btn-group">
            <button data-toggle="modal" data-target="#add-new-skill" class="btn btn-primary btn-save">@lang('admin.create-new-skill')</button>
          </div>

        </div>
      </div>
    </div>

    <br>

    @if (\Session::has('success'))
        <div class="alert alert-success">
            {!! \Session::get('success') !!}
        </div>
    @endif

    <div class="row">
        <div class="col-12">
          <div class="table-responsive">
            <table class="table table-hover table-striped bootg" id="skills-table">
                <thead>
                    <tr>
                        <th>Skill name</th>
                        <th>Description</th>
                    </tr>
                </thead>

                <tbody>
                  @if(isset($skills))
                    @foreach($skills as $skill)
                    <tr>
                      <td><a href="/skills/edit/{{{ $skill->id }}}">{{{ $skill->skill_name }}}</a></td>
                      <td>{{{ $skill->description }}}</td>
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

@include('includes/modals/create-skill')
@endsection
