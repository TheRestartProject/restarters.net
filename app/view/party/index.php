<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>


            <a class="btn btn-primary" href="/party/create"><i class="fa fa-plus"></i> New Party</a>
            <table class="table table-hover table-responsive sortable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Group</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Hours</th>
                        <th>Pax</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach($list as $p){ ?>
                    <tr>
                        <td><?php echo $p->id; ?></td>
                        <td>

                            <?php
                            if(hasRole($user, 'Administrator') || hasRole($user, 'Host') || $user->group == $g->id) {
                            ?>
                            <a href="/party/edit/<?php echo $p->id; ?>" title="edit party"><?php echo $p->group_name; ?></a>
                            <?php
                            } else {
                            ?>
                            <?php echo $p->group_name; ?>
                            <?php } ?>
                        </td>
                        <td><?php echo (!empty($p->venue) ? $p->venue  : $p->location); ?></td>
                        <td data-dateformat="DD/MM/YYYY"><?php echo strftime('%d/%m/%Y', $p->event_timestamp); ?></td>
                        <td><?php echo $p->start; ?></td>
                        <td><?php echo $p->end; ?></td>
                        <td><?php echo $p->hours; ?></td>
                        <td><?php echo $p->pax; ?></td>

                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
