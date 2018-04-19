
<form action="/party/manage/<?php echo $party->id; ?>" method="post" id="party-edit" enctype="multipart/form-data">
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1><?php _t("Edit Party");?>
                <small>
                    <button type="submit" id=btn_save onClick="onClickFun(this)" class="btn btn-primary btn-sm"><i class="fa fa-floppy-o"></i> <?php _t("save");?></button>

                    <a href="/party/edit/<?php echo $party->id; ?>" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> <?php _t("edit details");?></a>

                    <?php $home_url = (hasRole($user, 'Administrator') ? '/admin' : '/host'); ?>
                    <a href="<?php echo $home_url; ?>" class="btn btn-primary btn-sm"><i class="fa fa-home"></i> <?php _t("back to dashboard");?></a>

                </small>
            </h1>

        </div>
    </div>
    <!-- Profiles -->
    <?php if(hasRole( $user, 'Administrator' )) { ?>


    <section class="row profiles">
        <div class="col-md-12">
            <h5><?php _t("Admin Console");?></h5>

        </div>
        <div class="col-md-6">
            <div class="btn-group btn-group-justified">


                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php _t("Groups");?>
                      <span class="fa fa-chevron-down"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <?php foreach($grouplist as $group) { ?>
                        <li class="group-list clearfix">
                            <div class="pull-left">
                                <?php if(!empty($group->path)) { ?>
                                <img src="/uploads/thumbnail_<?php echo $group->path; ?>" width="40" height="40" alt="<?php echo $group->name; ?> Image" class="profile-pic" />
                                <?php } else { ?>
                                <div class="profile-pic clearfix" style="background: #ddd; width: 40px; height: 40px; ">&nbsp;</div>
                                <?php } ?>
                            </div>
                            <div class="pull-left">
                                <a  href="/host/index/<?php echo $group->id; ?>" ><?php echo $group->name; ?></a>
                            </div>
                        </li>

                        <?php } ?>
                    </ul>
                </div>

                <a class="btn btn-default" href="/group/create"><?php _t("Add Group");?></a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="btn-group btn-group-justified">
                <a class="btn btn-default" href="/user/all"><?php _t("Users");?></a>
                <a class="btn btn-default" href="/user/create"><?php _t("Add User");?></a>
            </div>
        </div>

    </section>



    <?php } ?>

    <div class="row">
        <div class="col-md-12">

            <div class="alert alert-info">
                <p>
                    This page lets you record the devices that were seen at your party, along with
                    information about the outcome of the repair.  Capturing this information gives
                    you a record of what your group has fixed, as well as statistics of the environmental
                    impact of your group's work (displayed on The Restart Project's website, and shareable
                    on your group's own website).  Further details about using this
                    page can be found <a class="alert-link" href="https://therestartproject.org/welcome-to-our-community-space/#Enteringdata">here</a>.
                </p>
                <p>
                 For guidance on completing each piece of information, you can click on the <i class="fa fa-question-circle"></i> icon next to the name of the field at the top of the list.
                </p>
            </div>
            <div class="alert alert-warning">
                <p>
                <?php echo WDG_PUBLIC_INFO; ?>.
                </p>
            </div>


            <input type="hidden" name="idparty" id="idparty" value="<?php echo $party->id; ?>">

            <div class="row party " >
                <div class="col-md-12">
                    <div class="header-col header-col-2">
                        <div class="date">
                            <span class="month"><?php echo date('M', $party->event_timestamp); ?></span>
                            <span class="day">  <?php echo date('d', $party->event_timestamp); ?></span>
                            <span class="year"> <?php echo date('Y', $party->event_timestamp); ?></span>
                        </div>

                        <div class="short-body">
                            <span class="location"><?php echo (!empty($party->venue) ? $party->venue : $party->location); ?></span><br />
                            <span class="groupname"><?php echo $party->group_name; ?></span>
                        </div>
                    </div>
                        <div class="data">
                            <div class="stat double">
                                <div class="col">
                                    <i class="fa fa-group"></i>
                                    <span class="subtext"><?php _t("participants");?></span>
                                </div>
                                <div class="col">
                                    <input class="party-input" name="party[pax]" value="<?php echo $party->pax; ?>" id="party[pax]">
                                </div>

                            </div>

                            <div class="stat double">
                                <div class="col">
                                    <img class="" alt="The Restart Project: Logo" src="/assets/images/logo_mini.png">
                                    <span class="subtext"><?php _t("restarters");?></span>
                                </div>
                                <div class="col">
                                    <input class="party-input" name="party[volunteers]" value="<?php echo $party->volunteers; ?>" id="party[volunteers]">
                                </div>

                            </div>

                            <div class="stat">

                                <div class="footprint">
                                    <?php echo $party->co2; ?>
                                    <span class="subtext"><?php _t("kg of CO<sub>2</sub>");?></span>
                                    <br />
                                    <?php echo number_format($party->ewaste, 0); ?>
                                    <span class="subtext"><?php _t("kg of waste");?><span>
                                </div>
                            </div>


                            <div class="stat fixed">
                                <div class="col"><i class="status mid fixed"></i></div>
                                <div class="col"><?php echo $party->fixed_devices; ?></div>
                            </div>

                            <div class="stat repairable">
                                <div class="col"><i class="status mid repairable"></i></div>
                                <div class="col"><?php echo $party->repairable_devices; ?></div>
                            </div>

                            <div class="stat dead">
                                <div class="col"><i class="status mid dead"></i></div>
                                <div class="col"><?php echo $party->dead_devices; ?></div>
                            </div>


                        </div>
                    </div>
                    <div class="col-md-12 text-right">
                      <button class="btn btn-default" type="button" data-toggle="modal" data-target="#esw"><i class="fa fa-share"></i> <?php _t("Share your stats");?></button>
                    </div>
                </div>
            </div>
            <!-- devices -->
            <div class="col-md-12">
              <h3><?php _t("Devices");?></h3>
            </div>


            <div class="col-md-12">
                <table class="table sticky-header" id="device-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php _t("Device Category");?>
                                <i class="fa fa-question-circle" data-toggle="popover" title="Device Category" data-html="true" data-content="<p>This is the category that the device best fits into.  You can find more information on the different categories <a href='https://therestartproject.org/welcome-to-our-community-space/#What_if_a_device_does_not_fit_in_any_of_the_categories'>here</a>.</p><p>If a device does not fit in any of the categories, you have the option of choosing 'None of the above' at the end of the drop-down menu. You will then be encouraged to estimate the weight of the device. Please use this option only as a last resort, and please ensure that you put the weight value in kilograms (e.g. 0.2 instead of 200).</p>"></i>
                            </th>
                            <th><?php _t("Device Details");?> <i class="fa fa-question-circle" data-toggle="popover" data-html="true" title="Information about the device" data-content="<p>Please provide as much information as is known about the device.</p><p><strong>Brand</strong>.  This is the company that makes the device.  Examples: Apple; Dyson; Sony.</p><p><strong>Model</strong>. This is the specific model of the device.  Examples: iPhone 5s;  DC50; Xperia Z1 Compact.</p><p><strong>Age</strong>. This is the age of the device in years, since the year of manufacture."></i></th>
                            <th><?php _t("Repair Comments");?> <i class="fa fa-question-circle" data-toggle="popover" title="Information about the repair attempt" data-html="true" data-content="<p>Please try and provide as much information as you can on the fault and on the solution or advice given (if any).  Information such as: what the fault was; what was the cause of the fault; what the solution was or could be.  Any further information that you think might be useful can be provided here as well.</p><p>For example: <br/><em>Cracked screen.  The phone had been dropped.  Recommended purchasing replacement screen and attending next party.</em></p><p>or</p><p><em>Would not turn on.  Fuse had blown.  Replaced fuse.</em></p>"></i></th>
<?php if (featureIsEnabled(FEATURE__DEVICE_PHOTOS)): ?>
                            <th style="width: 280px !important;"><?php _t("Image");?> <i class="fa fa-question-circle" data-toggle="popover" title="{REPLACE TITLE}" data-content="{REPLACE CONTENT}"></i></th>
<?php endif ?>
<th><?php _t("Repair Status");?> <i class="fa fa-question-circle" data-toggle="popover" title="The outcome of the repair attempt" data-html="true" data-content="<p><strong>Fixed</strong>. Have we prevented the purchase of another device? Will this device still be used?</p><p><strong>Repairable</strong>. If an owner of an unrepaired device will try to fix it at home, come back to another Restart Party, or get help from a friend or a professional.</p><p><strong>End of lifecyle</strong>. When a participant tells you they have given up, and are going to recycle a device.</p><p>See <a href='https://therestartproject.org/welcome-to-our-community-space/#Enteringdata'>here</a> for more detailed information.</p>"></i></th>
<th> <i class="fa fa-question-circle" data-toggle="popover" title="Spare parts required?" data-content="Whether a spare part (or parts) would be needed to complete the repair successfully.  Ticking this box does not necessarily indicate that the needed part(s) were available at the party, only that there is a need for an additional part(s).   Please be sure to indicate in the comments field what part is needed (if known) and whether it is available at the party."></i> <?php _t("Spare Parts?");?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        if(!empty($devices)){
                            for($i = 1; $i <= count($devices); $i++){
                        ?>



                        <tr class="rs-<?php echo $devices[$i-1]->repair_status; ?>">
                            <td>
                                <?php echo $i; ?>.
                                <input type="hidden" name="device[<?php echo $i; ?>][id]" value="<?php echo $devices[$i-1]->iddevices; ?>">

                            </td>
                            <td>
                                <div class="form-group">
                                    <select id="device[<?php echo $i; ?>][category]" name="device[<?php echo $i; ?>][category]" class="category-select form-control" data-live-search="true" required>
                                        <?php foreach($categories as $cluster){ ?>
                                        <optgroup label="<?php echo $cluster->name; ?>">
                                            <?php foreach($cluster->categories as $c){ ?>
                                            <option value="<?php echo $c->idcategories; ?>"<?php echo ($devices[$i-1]->category == $c->idcategories ? ' selected':''); ?>><?php echo $c->name; ?></option>
                                            <?php } ?>
                                        </optgroup>
                                        <?php } ?>
                                        <option value="46" <?php echo ($devices[$i-1]->category == 46 ? ' selected':''); ?>><?php _t("None of the above...");?></option>
                                    </select>
                                </div>                      
                                <div class="form-group
                                    <?php echo ($devices[$i-1]->category == 46 ? 'show' : 'hide'); ?>
                                     estimate-box">
                                    <small><?php _t("Please input an estimate weight (in kg)");?></small>
                                    <div class="input-group">
                                    <input type="number" step="00.01" min="0" max="99.99" name="device[<?php echo $i; ?>][estimate]" id="device[<?php echo $i; ?>][estimate]" class="form-control" placeholder="<?php _t("Estimate...");?>" value="<?php echo $devices[$i-1]->estimate; ?>">
                                    <span class="input-group-addon">kg</span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="form-group">
                                    <input type="text" name="device[<?php echo $i; ?>][brand]" id="device[<?php echo $i; ?>][brand]" class="form-control" placeholder="<?php _t("Brand - e.g. Apple, Dyson");?>" value="<?php echo $devices[$i-1]->brand; ?>">
                                </div>

                                <div class="form-group">
                                    <input type="text" name="device[<?php echo $i; ?>][model]" id="device[<?php echo $i; ?>][model]" class="form-control" placeholder="<?php _t("Model - e.g. iPhone 5s, DC50");?>" value="<?php echo $devices[$i-1]->model; ?>">
                                </div>

                                <div class="form-group">
                                    <?php $ageInputType = (featureIsEnabled(FEATURE__DEVICE_AGE)) ? "text" : "hidden"; ?>
                                    <input type="<?php echo $ageInputType; ?>" name="device[<?php echo $i; ?>][age]" id="device[<?php echo $i; ?>][age]" class="form-control" placeholder="<?php _t("Age - e.g. 3 years");?>" value="<?php echo $devices[$i-1]->age; ?>">
                                </div>

                            </td>

                            <td>
                                <textarea rows="6" class="form-control" id="device[<?php echo $i; ?>][problem]" name="device[<?php echo $i; ?>][problem]"><?php echo $devices[$i-1]->problem; ?></textarea>
                            </td>

                            <?php if (featureIsEnabled(FEATURE__DEVICE_PHOTOS)): ?>
                            <td>

                              <?php if(!empty($devices[$i-1]->path)) { ?>
                                <div class="device-img-wrap">
                                  <a href="#" class="device-image-delete pull-right" data-device-image="<?php echo $devices[$i-1]->idimages; ?>"><i class="fa fa-times"></i></a>
                                  <a href="#" data-toggle="modal" data-target="#device-img-modal">
                                    <img src="/public/uploads/<?php echo $devices[$i-1]->path; ?>" class="img-responsive device-img">
                                  </a>
                                </div>
                              <?php } ?>

                              <div class="form-group">
                                <input type="file" class="form-control file" name="device[<?php echo $i; ?>][image]"
                                data-show-upload="false"
                                data-show-caption="true"
                                data-preview-file-icon="<i class='fa fa-file'></i>",
                                data-browse-icon="<i class='fa fa-folder-open'></i> &nbsp;",
                                data-upload-icon="<i class='fa fa-upload'></i>"
                                data-remove-icon="<i class='fa fa-trash'></i>"
                                data-cancel-icon="<i class='fa fa-ban-circle'></i>"
                                data-file-icon="<i class='fa fa-file'></i>"
                                >
                              </div>

                            </td>
                            <?php endif ?>

                            <td>
                                <div class="form-group">
                                    <div class="radio">
                                        <label>
                                            <input
                                              type="radio"
                                              name="device[<?php echo $i; ?>][repair_status]"
                                              id="device[<?php echo $i; ?>][repair_status_1]"
                                              value="1"
                                              <?php echo ($devices[$i-1]->repair_status == 1 ? 'checked="checked"' : ''); ?>>
                                              <?php _t("Fixed");?>
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input
                                                   type="radio"
                                                   <?php echo ($devices[$i-1]->repair_status == 2 ? 'checked="checked"' : ''); ?>
                                                   name="device[<?php echo $i; ?>][repair_status]"
                                                   id="device[<?php echo $i; ?>][repair_status_2]"
                                                   value="2"
                                                   class="repairable"
                                                   data-target-details="#repairable-details-<?php echo $i; ?>">

                                                   <?php _t("Repairable");?>
                                        </label>
                                    </div>
                                    <div id="repairable-details-<?php echo $i; ?>" class="repairable-details">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="device[<?php echo $i; ?>][more_time_needed]" id="device[<?php echo $i; ?>][more_time_needed]" value="1" <?php echo ($devices[$i-1]->more_time_needed == 1 ? 'checked' : ''); ?> > <?php _t("More time needed");?>
                                            </label>
                                        </div>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="device[<?php echo $i; ?>][professional_help]" id="device[<?php echo $i; ?>][professional_help]" value="1" <?php echo ($devices[$i-1]->professional_help == 1 ? 'checked' : ''); ?> > <?php _t("Professional help");?>
                                            </label>
                                        </div>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="device[<?php echo $i; ?>][do_it_yourself]" id="device[<?php echo $i; ?>][do_it_yourself]" value="1" <?php echo ($devices[$i-1]->do_it_yourself == 1 ? 'checked' : ''); ?> > <?php _t("Do it yourself");?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input
                                                   type="radio"
                                                   name="device[<?php echo $i; ?>][repair_status]"
                                                   id="device[<?php echo $i; ?>][repair_status_3]"
                                                   value="3"
                                                   <?php echo ($devices[$i-1]->repair_status == 3 ? 'checked="checked"' : ''); ?>> <?php _t("End of lifecycle");?>
                                        </label>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                  <div class="checkbox">
                                    <label>
                                      <input type="hidden" name="device[<?php echo $i; ?>][spare_parts]" id="device[<?php echo $i; ?>][spare_parts_2]" value="2">
                                      <input type="checkbox" name="device[<?php echo $i; ?>][spare_parts]" id="device[<?php echo $i; ?>][spare_parts_1]" value="1" <?php echo ($devices[$i-1]->spare_parts == 1 ? 'checked' : ''); ?>> Yes
                                    </label>
                                  </div>
                                </div>
                            </td>
                            <td>
                                <a class="btn delete-control" href="/device/delete/<?php echo $devices[$i-1]->iddevices; ?>"><i class="fa fa-trash"></i></a>
                            </td>

                        </tr>
                        <?php
                            }
                        }
                        ?>


                        <?php
                        $start = (!empty($devices) ? count($devices) + 1 : 1);


                        for ($i = $start; $i < $start  ; $i++) {
                        ?>
                        <tr>
                            <td><?php echo $i; ?>.</td>
                            <td>
                                <div class="form-group">
                                    <select id="device[<?php echo $i; ?>][category]" name="device[<?php echo $i; ?>][category]" class="selectpicker form-control category-select" data-live-search="true" title="Choose category..." required>
                                        <option></option>
                                        <?php foreach($categories as $cluster){ ?>
                                        <optgroup label="<?php echo $cluster->name; ?>">
                                            <?php foreach($cluster->categories as $c){ ?>
                                            <option value="<?php echo $c->idcategories; ?>"><?php echo $c->name; ?></option>
                                            <?php } ?>
                                        </optgroup>
                                        <?php } ?>
                                        <option value="46"><?php _t("None of the above...");?></option>
                                    </select>
                                </div>
                                <div class="form-group hide estimate-box">
                                    <small><?php _t("Please input an estimate weight (in kg)");?></small>
                                    <div class="input-group">
                                        <input type="number" step="00.01" min="0" max="99.99" name="device[<?php echo $i; ?>][estimate]" id="device[<?php echo $i; ?>][estimate]" class="form-control" placeholder="<?php _t("Estimate...");?>">
                                        <span class="input-group-addon">kg</span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                 <div class="form-group">
                                    <input type="text" name="device[<?php echo $i; ?>][brand]" id="device[<?php echo $i; ?>][brand]" class="form-control" placeholder="<?php _t("Brand - e.g. Apple, Dyson");?>">
                                </div>

                                <div class="form-group">
                                    <input type="text" name="device[<?php echo $i; ?>][model]" id="device[<?php echo $i; ?>][model]" class="form-control" placeholder="<?php _t("Model - e.g. iPhone 5s, DC50");?>" >
                                </div>

                                <div class="form-group">
                                    <input type="hidden" name="device[<?php echo $i; ?>][age]" id="device[<?php echo $i; ?>][age]" class="form-control" placeholder="<?php _t("Age - e.g. 3 years");?>" >
                                </div>
                            </td>

                            <td>
                                <textarea rows="6" class="form-control" id="device[<?php echo $i; ?>][problem]" name="device[<?php echo $i; ?>][problem]"></textarea>
                            </td>

                            <?php if (featureIsEnabled(FEATURE__DEVICE_PHOTOS)): ?>
                            <td>
                              <div class="form-group">
                                <input type="file" class="form-control file" name="device[<?php echo $i; ?>][image]" data-show-upload="false" data-show-caption="true">
                              </div>
                            </td>
                            <?php endif ?>

                            <td>
                                <div class="form-group">
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="device[<?php echo $i; ?>][repair_status]" id="device[<?php echo $i; ?>][repair_status_1]" value="1" checked> <?php _t("Fixed");?>
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" class="repairable" data-target-details="#repairable-details-<?php echo $i; ?>" name="device[<?php echo $i; ?>][repair_status]" id="device[<?php echo $i; ?>][repair_status_2]" value="2"> <?php _t("Repairable");?>
                                        </label>
                                    </div>
                                    <div id="repairable-details-<?php echo $i; ?>" class="repairable-details">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="device[<?php echo $i; ?>][more_time_needed]" id="device[<?php echo $i; ?>][more_time_needed]" value="1"> <?php _t("More time needed");?>
                                            </label>
                                        </div>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="device[<?php echo $i; ?>][professional_help]" id="device-<?php echo $i; ?>[professional_help]" value="1"> <?php _t("Professional help");?>
                                            </label>
                                        </div>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="device[<?php echo $i; ?>][do_it_yourself]" id="device[<?php echo $i; ?>][do_it_yourself]" value="1"> <?php _t("Do it yourself");?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="device[<?php echo $i; ?>][repair_status]" id="device[<?php echo $i; ?>][repair_status_3]" value="3"> <?php _t("End of lifecycle");?>
                                        </label>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <div class="checkbox">
                                        <label>
                                            <input type="hidden" name="device[<?php echo $i; ?>][spare_parts]" id="device[<?php echo $i; ?>][spare_parts_2]" value="2">
                                            <input type="checkbox" name="device[<?php echo $i; ?>][spare_parts]" id="device[<?php echo $i; ?>][spare_parts_1]" value="1"> <?php _t("Yes");?>
                                        </label>
                                    </div>

                                </div>
                            </td>

                        </tr>
                        <?php } ?>


                    </tbody>
                    <tfoot>
                         <tr>
                            <td colspan="3"><button class="btn btn-primary text-center" type="button" id="add-device"><i class="fa fa-plus"></i> <?php _t("Add Device");?></button></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="text-center">
                    <br /><br />
                    <button type="submit" id="btn_save" onClick="onClickFun(this)" class="btn btn-primary btn-lg"><i class="fa fa-floppy-o"></i> <?php _t("Save");?></button>

                    <script type="text/javascript">
                        function onClickFun(el){
                            el.innerHTML='Saving...';
                            el.addEventListener("click", function(event){
                                event.preventDefault()
                            });
                            
                        }
                    </script>

                    <br /><br /><br />
                </div>
            </div>
        </div>
    </div>
</div>
</form>


<!-- MODAL DIALOG FOR SHARING STATISTICS -->
<div class="modal fade" tabindex="-1" role="dialog" id="esw">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php _t("Share your party's stats");?></h4>
      </div>
      <div class="modal-body">
        <p><?php _t("Copy and paste this code snippet into a page on your website to share your party achievements!");?></p>
        <div><strong><?php _t("Headline stats");?></strong></div>
        <p><?php _t("This widget shows the headline stats for your party &mdash; the number of participants, number of Restarters, the CO<sub>2</sub> and waste diverted, and the numbers of fixed, repairable, and end-of-life devices");?>
        </p>
        <code style="padding:0">
            <pre>&lt;iframe src="https://community.therestartproject.org/party/stats/<?php echo $party->id; ?>/wide" frameborder="0" width="100%" height="80"&gt;&lt;/iframe&gt;</pre>
        </code>
        <div><strong><?php _t("CO<sub>2</sub> equivalence visualisation");?></strong></div>
        <p><?php _t("This widget displays an infographic of an easy-to-understand equivalent of the CO<sub>2</sub> emissions that this party has diverted, such as equivalent number of cars manufactured.");?></p>
            <code style="padding:0">
              <pre>&lt;iframe src="https://community.therestartproject.org/outbound/info/party/<?php echo $party->id; ?>" frameborder="0" width="700" height="1050"&gt;&lt;/iframe&gt;</pre>
            </code>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php _t("Close");?></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->



<!-- MODAL DIALOG FOR VIEWING IMAGES -->
<div class="modal fade" tabindex="-1" role="dialog" id="device-img-modal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php _t("Device S/N Image");?></h4>
      </div>
      <div class="modal-body">

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php _t("Close");?></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
