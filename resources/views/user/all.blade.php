@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-4">
            <h1>User List</h1>

            <a class="btn btn-primary" href="/user/create"><i class="fa fa-plus"></i> New User</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-hover table-responsive sortable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>&nbsp;</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Permissions</th>
                        <th>Last Login</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($userlist as $u)
                    <tr>
                        <td><?php echo $u->id; ?></td>
                        <td><a href="/user/profile/<?php echo $u->id; ?>"><i class="fa fa-user"></i></a></td>
                        <td>

                            @if(FixometerHelper::hasRole($user, 'Administrator'))
                            <a href="/user/edit/<?php echo $u->id; ?>"><?php echo $u->name; ?></a>
                            @else
                            <?php echo $u->name; ?>
                            @endif

                        </td>
                        <td><?php echo $u->email; ?></td>
                        <td><?php echo $u->role; ?></td>
                        <td><?php foreach ($u->permissions as $permission) { echo $permission->permission . ' '; } ?></td>
                        <td data-value="<?php echo $u->modified_at; ?>" ><?php echo FixometerHelper::dateFormat($u->modified_at); ?></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
</div>
@endsection
