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

    <div class="row">
        <div class="col-12">
          <div class="table-responsive">
            <table class="table table-hover table-striped bootg" id="skills-table">
                <thead>
                    <tr>
                        <th data-column-id="skillID"  data-header-css-class="comm-cell" data-identifier="true" data-type="numeric">#</th>
                        <th data-column-id="skill">Skill</th>
                        <th data-column-id="skill-description">Description</th>
                        <th data-column-id="created-at">Created At</th>
                        <th data-column-id="updated-at">Updated At</th>
                        <th data-column-id="edit" data-header-css-class="comm-cell" data-formatter="editLink" data-sortable="false">Actions</th>
                    </tr>
                </thead>

                <tbody>
                  @if(isset($skills))
                    @foreach($skills as $skill)
                    <tr>
                      <td><?php echo $skill->id; ?></td>
                      <td><?php echo $skill->skill_name; ?></td>
                      <td><?php echo $skill->description; ?></td>
                      <td><?php echo $skill->created_at; ?></td>
                      <td><?php echo $skill->updated_at; ?></td>
                      <td>
                        <a href="/skills/edit/<?php echo $skill->id; ?>" class="btn btn-warning">edit</a>
                        <a href="/skills/delete/<?php echo $skill->id; ?>" class="btn btn-danger">delete</a>
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

@include('includes/modals/create-skill')
@endsection
