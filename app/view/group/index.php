<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>
            <?php if($response) { printResponse(parseResponse($response)); } ?>
            <a class="btn btn-primary" href="/group/create"><i class="fa fa-plus"></i> <?php _t("New Group");?></a>
            <table class="table table-hover table-responsive sortable">
                <thead>
                    <tr>
                        <th><?php _t("ID");?></th>
                        <th><?php _t("Group");?></th>
                        <th><?php _t("Location");?></th>
                        <th><?php _t("Frequency");?></th>
                        <th><?php _t("Restarters");?></th>
                        <?php if(hasRole($user, 'Administrator')){ ?>
                        <th><i class="fa fa-pencil"></i></th>
                        <th><i class="fa fa-trash"></i></th>
                        <?php } ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach($list as $g){ ?>
                    <tr>
                        <td><?php echo $g->id; ?></td>
                        <td><a href="/group/edit/<?php echo $g->id; ?>" title="edit group"><?php echo $g->name; ?></a></td>
                        <td><?php echo $g->location . ', ' . $g->area; ?></td>
                        <td><?php echo $g->frequency; ?> <?php _t("Parties/Year");?></td>
                        <td><?php echo $g->user_list; ?></td>
                        <?php if(hasRole($user, 'Administrator')){ ?>
                        <td><a href="/group/edit/<?php echo $g->id; ?>"><i class="fa fa-pencil"></i></a></td>
                        <td><a href="/group/delete/<?php echo $g->id; ?>" class="delete-control"><i class="fa fa-trash"></i></a></td>
                        <?php } ?>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
