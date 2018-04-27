<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1>Roles</h1>
            <table class="table table-hover table-responsive sortable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Role</th>
                        <th>Permissions</th>
                    </tr>
                </thead>
                
                <tbody>
                    <?php foreach($roleList as $role){ ?>
                    <tr>
                        <td><?php echo $role->id; ?></td>
                        <td><a href="/role/edit/<?php echo $role->id; ?>" title="edit role permissions"><?php echo $role->role; ?></a></td>
                        <td><?php echo $role->permissions_list; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div> 
    </div>
</div>