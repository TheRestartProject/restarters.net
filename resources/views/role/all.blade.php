@extends('layouts.app')
@section('content')
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="d-flex justify-content-between">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{{ route('dashboard') }}}">FIXOMETER</a></li>
              <li class="breadcrumb-item active" aria-current="page">ROLES</li>
            </ol>
          </nav>
          <div class="">
            <a href="#" class="btn btn-primary disabled" aria-disabled="true">Create new role</a>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Name</th>
                <th>Permissions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($roleList as $role)
              <tr>
                  <td><a href="/role/edit/<?php echo $role->id; ?>" title="edit role permissions"><?php echo $role->role; ?></a></td>
                  <td><?php echo $role->permissions_list; ?></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection
