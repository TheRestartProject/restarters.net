<div class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-4">
            <h1><?php echo $title; ?>
            <small>
                    <?php $home_url = (hasRole($user, 'Administrator') ? '/admin' : '/host'); ?>
                    <a href="<?php echo $home_url; ?>" class="btn btn-primary btn-sm"><i class="fa fa-home"></i> <?php _t("back to dashboard");?></a>
            </small>
            </h1>
        </div>
    </div>
    <div class="row">
        <div class="container">

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
                    <ul id ="group-dropdown" class="dropdown-menu">
                        <?php foreach($grouplist as $group) { ?>
                        <li class="group-list clearfix">
                            <div class="pull-left">
                                <?php if(!empty($group->path)) { ?>
                                <img src="/uploads/thumbnail_<?php echo $group->path; ?>" width="40" height="40" alt="<?php echo $group->name; ?> Image" class="profile-pic" />
                                <?php } else { ?>
                                <div class="profile-pic clearfix" style="background: #ddd; width: 40px; height: 40px; ">&nbsp;</div>
                                <?php } ?>
                            </div>
                            <div class="pull-left group-option">
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

                    <?php if(isset($response)) { printResponse($response); } ?>
                    <div class="alert alert-info">
                        <p>
                        This page allows you to create a party for your group.  You can find more detailed information about how to use this page <a href="https://therestartproject.org/welcome-to-our-community-space/#Create_an_upcoming_Restart_Party_to_announce_it" class="alert-link" target="_blank">here</a>.

                        </p>
                        <p>
                            For guidance on completing each piece of information, you can click on the <i class="fa fa-question-circle"></i> icon next to the name of the field.  
                        </p>
                    </div>
                    <div class="alert alert-warning">
                        <p>
                        <?php echo WDG_PUBLIC_INFO; ?>.
                        </p>
                    </div>
                    <form action="/party/create" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">

                                <div class="form-group <?php if(isset($error) && isset($error['event_date']) && !empty($error['event_date'])) { echo "has-error"; } ?>">
                                    <label for="event_date">Date:  </label>
                                    <i class="fa fa-question-circle" data-toggle="popover" title="Date party takes place" data-content="This is the date that the party will happen on.  You can select the date from the calendar."></i>
                                    <div class="input-group date">
                                        <input type="text" name="event_date" id="event_date" class="form-control date">
                                        <span class="input-group-addon">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                    </div>
                                    <?php if(isset($error) && isset($error['start']) && !empty($error['start'])) { echo '<span class="help-block text-danger">' . $error['start'] . '</span>'; } ?>
                                </div>


                                <div class="form-group">
                                    <label for="free_text"><?php _t("Description:");?></label>
                                    <i class="fa fa-question-circle" data-toggle="popover" title="Promotional description of your event" data-content="This is a description of the party that is displayed to members of the public to in advance of the party.  This information is published on The Restart Project website, and helps to promote your event."></i>
                                    <textarea class="form-control rte" rows="6" name="free_text" id="free_text"></textarea>
                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group <?php if(isset($error) && isset($error['start']) && !empty($error['start'])) { echo "has-error"; } ?>">
                                            <label for="start">Start: </label>
                                            <i class="fa fa-question-circle" data-toggle="popover" title="Start time of the party" data-content="This is the time that the party starts at, i.e. the time that members of the public should arrive at.  You can select the time using the timepicker."></i>
                                            <div class="input-group time">
                                                <input type="text" name="start" id="start-pc" class="form-control time">
                                                <span class="input-group-addon">
                                                    <i class="fa fa-clock-o"></i>
                                                </span>
                                            </div>
                                            <?php if(isset($error) && isset($error['start']) && !empty($error['start'])) { echo '<span class="help-block text-danger">' . $error['start'] . '</span>'; } ?>
                                        </div>

                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group <?php if(isset($error) && isset($error['end']) && !empty($error['end'])) { echo "has-error"; } ?>">
                                            <label for="end">End: </label>
                                            <i class="fa fa-question-circle" data-toggle="popover" title="End time of the party" data-content="This is the time the party finishes at, i.e. when fixing stops and members of the public need to leave.  You can select the time using the timepicker."></i>
                                            <div class="input-group time">
                                                <input type="text" name="end" id="end-pc" class="form-control time">
                                                <span class="input-group-addon">
                                                    <i class="fa fa-clock-o"></i>
                                                </span>
                                            </div>
                                            <?php if(isset($error) && isset($error['end']) && !empty($error['end'])) { echo '<span class="help-block text-danger">' . $error['end'] . '</span>'; } ?>
                                        </div>

                                    </div>

                                </div>

                                    <input type="hidden" name="pax" id="pax" value="0">
                                    <input type="hidden" name="volunteers" id="volunteers" value="0">

                                <?php
                                if(hasRole($user, 'Host') && !hasRole($user, 'Root')) {
                                ?>

                                <input type="hidden" name="group" id="group" value="<?php echo $usergroup->idgroups; ?>">

                                <?php
                                }
                                else {
                                ?>

                                <div class="form-group <?php if(isset($error) && isset($error['group']) && !empty($error['group'])) { echo "has-error"; } ?>">
                                    <label for="group">Group: </label>
                                    <i class="fa fa-question-circle" data-toggle="popover" title="Group hosting the party" data-content="This is the Restart group that is hosting the party."></i>
                                    <select id="group" name="group"  class="form-control selectpicker users_group">
                                        <option></option>
                                        <?php foreach($group_list as $group){ ?>
                                        <option value="<?php echo $group->id; ?>"><?php echo $group->name; ?></option>
                                        <?php } ?>
                                    </select>
                                    <?php if(isset($error) && isset($error['group']) && !empty($error['group'])) { echo '<span class="help-block text-danger">' . $error['group'] . '</span>'; } ?>

                                    <div class="users_group_list">

                                    </div>
                                </div>
                                <?php
                                }
                                ?>
                                <div class="form-group">
                                    <label for="venue"><?php _t("Party Name:");?> </label>
                                    <i class="fa fa-question-circle" data-toggle="popover" title="Short name of the party" data-content="This is a short name for the party that is used on The Restart Project website as part of the promotion of your party. The recommended style for this is to use the name of the city/town/village/hamlet for the party name (e.g. Andover) or, if in a larger city, to use a specific neighbourhood (e.g. Tooting instead of London), or even a specific venue name (e.g. The Goodlife Centre).  Try to avoid using the term 'Restart Party' or other variant."></i>
                                    <input type="text" name="venue" id="venue" class="form-control" placeholder="Neighbourhood or the name of the venue - no 'Restart Party' or 'Repair Cafe'" <?php if(isset($error) && !empty($error) && !empty($udata)) echo 'value="'.$udata['venue'].'"' ; ?>>
                                </div>
                                <div class="form-group">
                                    <label for="location"><?php _t("Location:");?> </label>
                                    <i class="fa fa-question-circle" data-toggle="popover" title="Location of the party" data-content="This is the location of your party, and is used to display where your party is on a map to members of the public.  Please fill in the address of the party here, including e.g. venue name, street address, and postcode, and then press the 'geocode' button.  This will then show you on the map below the location that will be displayed to members of the public on The Restart Project website.  If it isn't correct, please try entering a more specific address and pressing 'geocode' again."></i>
                                    <div class="input-group">
                                        <input type="text" name="location" id="location" class="form-control" <?php if(isset($error) && !empty($error) && !empty($udata)) echo 'value="'.$udata['location'].'"' ; ?>>
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-primary" onclick="codeAddress()"><i class="fa fa-map-marker"></i> <?php _t("geocode");?></button>
                                        </span>


                                    </div>
                                    <!-- <p class="help-block">
                                        <?php _t("To pinpoint the party venue on the map, please enter the venue name and the address in the fields above, then press \"geocode\".");?>
                                    </p>-->
                                </div>


                                <div class="" id="map-canvas" style="height: 350px; ">
                                    <i class="fa fa-spinner"></i>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                          <input type="text" name="latitude" id="latitude" class="form-control" placeholder="<?php _t("latitude...");?>" <?php if(isset($error) && !empty($error) && !empty($udata)) echo 'value="'.$udata['latitude'].'"' ; ?>>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <input type="text" name="longitude" id="longitude" class="form-control" placeholder="<?php _t("longitude...");?>" <?php if(isset($error) && !empty($error) && !empty($udata)) echo 'value="'.$udata['longitude'].'"' ; ?>>
                                        </div>
                                    </div>
                                </div>


                                <div class="from-group">
                                    <label for="file" class="sr-only"><?php _t("Image:");?></label>
                                    <input type="file" name="file" id="file" class="form-control fileinput">
                                    <i class="fa fa-question-circle" data-toggle="popover" title="Uploaded images" data-content="Images uploaded here are displayed on your party's public page on The Restart Project website.  NOTE: not currently implemented, coming soon!  You can upload images, but they are not currently displayed on the public page."></i>
                                </div>

                            </div>

                        </div>
                        <div class="row buttons">

                            <div class="col-md-6 col-md-offset-6">

                                <div class="form-group">
                                    <button class="btn btn-default" type="reset"><i class="fa fa-refresh"></i> <?php _t("reset");?></button>
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> <?php _t("save");?></button>
                                </div>

                            </div>

                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
