<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>
            <a class="btn btn-default btn-sm" href="/device/create"><i class="fa fa-plus"></i> <?php _t("New Device");?></a>
            <a href="/export/devices" class="btn btn-default btn-sm"><i class="fa fa-download"></i> <?php _t("All Device Data");?></a>
            <hr />
        </div>
        <div class="col-md-1">
            <h2><?php _t("Search");?></h2>

        </div>
        <div class="col-md-11">
          <form action="/device/index" method="get">
            <input type="hidden" name="fltr" value="<?php echo bin2hex(openssl_random_pseudo_bytes(8)); ?>">
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <select id="categories" name="categories[]" class="selectpicker form-control" multiple data-live-search="true" title="<?php _t("Choose categories...");?>">
                    <?php foreach($categories as $cluster){ ?>
                    <optgroup label="<?php echo $cluster->name; ?>">
                      <?php foreach($cluster->categories as $c){ ?>
                      <option value="<?php echo $c->idcategories; ?>"
                        <?php
                        if(isset($_GET['categories']) && !empty($_GET['categories'])){
                          foreach($_GET['categories'] as $cat){
                            if ($cat == $c->idcategories) { echo " selected "; }
                          }
                        }
                        ?>
                      >
                      <?php echo $c->name; ?>
                      </option>
                      <?php } ?>
                    </optgroup>
                    <?php } ?>
                    <option value="46">Misc</option>
                  </select>

                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <select id="groups" name="groups[]" class="selectpicker form-control" multiple data-live-search="true" title="<?php _t("Choose groups...");?>">
                    <?php foreach($groups as $g){ ?>
                    <option value="<?php echo $g->id; ?>"
                      <?php
                      if(isset($_GET['groups']) && !empty($_GET['groups'])){
                        foreach($_GET['groups'] as $grp){
                          if ($grp == $g->id) { echo " selected "; }
                        }
                      }
                      ?>
                    >
                    <?php echo $g->name; ?>
                    </option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <div class="input-group date from-date">
                    <input type="text" class="form-control" id="search-from-date" name="from-date" placeholder="<?php _t("From date...");?>" <?php if(isset($_GET['from-date']) && !empty($_GET['from-date'])){ echo ' value="' . $_GET['from-date'] . '"'; } ?> >
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <div class="input-group date to-date">
                    <input type="text" class="form-control" id="search-to-date" name="to-date" placeholder="<?php _t("To date...");?>" <?php if(isset($_GET['to-date']) && !empty($_GET['to-date'])){ echo ' value="' . $_GET['to-date'] . '"'; } ?> >
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">

              <div class="col-md-3">
                <div class="form-group">
                  <input type="text" class="form-control " id="brand" name="brand" placeholder="<?php _t("Brand...");?>" <?php if(isset($_GET['brand']) && !empty($_GET['brand'])){ echo ' value="' . $_GET['brand'] . '"'; } ?> >
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <input type="text" class="form-control " id="model" name="model" placeholder="<?php _t("Model...");?>" <?php if(isset($_GET['model']) && !empty($_GET['model'])){ echo ' value="' . $_GET['model'] . '"'; } ?> >
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <input type="text" class="form-control " id="free-text" name="free-text" placeholder="<?php _t("Search in the comment...");?>"  <?php if(isset($_GET['free-text']) && !empty($_GET['free-text'])){ echo ' value="' . $_GET['free-text'] . '"'; } ?> >
                </div>
              </div>

              <div class="col-md-1">
                <button class="btn btn-primary btn-block"><i class="fa fa-search"></i> <?php _t("Search");?></button>
              </div>

              <div class="col-md-1">
                <a href="/device/index" class="btn btn-default btn-block"><i class="fa fa-refresh"></i> <?php _t("Reset");?></a>
              </div>
            </div>
          </form>
        </div>

        <div class="col-md-12">

            <table class="table table-hover table-responsive table-striped bootg" id="devices-table">
                <thead>
                    <tr>
                        <th data-column-id="deviceID"  data-header-css-class="comm-cell" data-identifier="true" data-type="numeric">#</th>
                        <th data-column-id="category"><?php _t("Category");?></th>
                        <th data-column-id="brand"><?php _t("Brand");?></th>
                        <th data-column-id="model"><?php _t("Model");?></th>
                        <th data-column-id="comment"><?php _t("Comment");?></th>
                        <th data-column-id="groupName"><?php _t("Event (Group)");?></th>
                        <th data-column-id="eventDate" data-header-css-class="mid-cell"><?php _t("Event Date");?></th>
                        <th data-column-id="location">Location</th>
                        <th data-column-id="repairstatus" data-header-css-class="mid-cell" data-formatter="statusBox"><?php _t("Repair state");?></th>
                        <th data-column-id="edit" data-header-css-class="comm-cell" data-formatter="editLink" data-sortable="false"><?php _t("edit");?></th>
                    </tr>
                </thead>

                <tbody>
                  <?php foreach($list as $device){ ?>
                  <tr>
                    <td><?php echo $device->id; ?></td>
                    <td><?php echo $device->category_name; ?></td>
                    <td><?php echo $device->brand; ?></td>
                    <td><?php echo $device->model; ?></td>
                    <td><?php echo $device->problem; ?></td>
                    <td><?php echo $device->group_name; ?></td>
                    <td><?php echo strftime('%Y-%m-%d', $device->event_date); ?></td>
                    <td><?php echo $device->event_location; ?></td>
                    <td><?php echo $device->repair_status; ?></td>
                    <td><a href="/device/edit/<?php echo $device->id; ?>">edit</a></td>
                  </tr>
                  <?php } ?>
                </tbody>
            </table>

        </div>
    </div>
</div>


<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="deviceEditor">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
