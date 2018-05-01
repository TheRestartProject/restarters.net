<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>



            <div class="row">
                <div class="col-md-12">
                    <span><?php _t("Legend");?></span>
                    <ul class="legend">
                        <li class="voice indicator indicator-1"><?php _t("Very poor");?></li>
                        <li class="voice indicator indicator-2"><?php _t("Poor");?></li>
                        <li class="voice indicator indicator-3"><?php _t("Fair");?></li>
                        <li class="voice indicator indicator-4"><?php _t("Good");?></li>
                        <li class="voice indicator indicator-5"><?php _t("Very good");?></li>
                    </ul>
                </div>
            </div>
            <table class="table table-hover table-responsive sortable">
                <thead>
                    <tr>

                        <th><?php _t("ID");?></th>
                        <th><?php _t("Name");?></th>
                        <th><?php _t("Weight");?> [kg]</th>
                        <th><?php _t("CO<sub>2</sub> Footprint)");?> [kg]</th>
                        <th width="145"><?php _t("Reliability");?></th>

                    </tr>
                </thead>

                <tbody>
                    <?php foreach($list as $p){ ?>
                    <tr>
                        <td><?php echo $p->idcategories; ?></td>
                        <td><?php echo $p->name; ?></td>
                        <td><?php echo $p->weight; ?></td>
                        <td><?php echo $p->footprint; ?></td>
                        <td><span class="indicator indicator-<?php echo $p->footprint_reliability; ?>"><?php echo $p->footprint_reliability; ?></span></td>

                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
