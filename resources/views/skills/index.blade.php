@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>
            <a class="btn btn-default btn-sm" href="/skills/create"><i class="fa fa-plus"></i>New Skill</a>
            <hr>
        </div>
    </div>

    <div class="col-md-12">

        <table class="table table-hover table-responsive table-striped bootg" id="skills-table">
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

@endsection
