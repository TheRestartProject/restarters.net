@extends('layouts.app')
@section('content')
<section class="admin">
    <div class="container">
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


        <div class="row mb-30">
            <div class="col-12 col-md-12">
                <div class="d-flex align-items-center">
                    <h1 class="mb-0 mr-30">
                        Roles
                    </h1>

                </div>
            </div>
        </div>

        <br>
        <div class="row">
            <div class="col-12">
                <div class="table-responsive table-section">
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
</section>
@endsection
