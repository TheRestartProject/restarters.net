<div class="container">
    <div class="row host-header">

        <div class="col-md-1">
            <a href="/admin">
                <img class="img-responsive" alt="The Restart Project: Logo" src="/assets/images/logo_mini.png">
            </a>
        </div>

        <div class="col-md-8">
            <span class="">Welcome, <strong><?php echo $user->name; ?></strong></span><br />

            <?php if ($showbadges) { ?>
                <span class="">There are <a class="label label-warning" href=""><?php echo $showbadges; ?></a> guesstimated devices.</span>
            <?php  } ?>
        </div>

        <div class="col-md-1 col-md-offset-1">

            <div class="dropdown">
                <button class="btn btn-default dropdown-toggle" type="button" id="profileDropDown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <?php if(empty($profile->path)){ ?>
                    <img src="/assets/images/logo_mini.png" alt="<?php echo $profile->name; ?> Image" class="profile-pic" width="40" height="40" />
                    <?php } else { ?>
                    <img src="/uploads/thumbnail_<?php echo $profile->path; ?>" width="40" height="40" alt="<?php echo $profile->name; ?> Image" class="profile-pic" />
                    <?php } ?>
                    <i class="fa fa-caret-down"></i>
                </button>

                 <ul class="dropdown-menu" aria-labelledby="profileDropDown">
                    <li><a href="/user/edit/<?php echo $profile->idusers; ?>" class="small"><i class="fa fa-edit"></i> <?php _t("Edit Profile");?></a></li>
                    <li><a href="https://www.facebook.com/groups/RestartHosts/" target="_blank"><i class="fa fa-facebook"></i> <?php _t("Facebook Group");?></a></li>
                    <li><a class="" href="/user/logout"><i class="fa fa-sign-out"></i> <?php _t("Logout");?></a></li>
                  </ul>

            </div>


        </div>
        <div class="col-md-1">
            <a class="btn btn-link pull-right" href="http://therestartproject.org/welcome-to-our-community-space/" target="_blank">
                <i class="fa fa-question-circle fa-2x fa-fw"></i>
            </a>
        </div>
    </div>

</div>

<?php /*
       *  <div class="col-md-6">
            <strong>My Profile</strong> <a href="/user/edit/<?php echo $profile->idusers; ?>" class="small"><i class="fa fa-edit"></i> Edit Profile...</a>
            <div class="media">
                <div class="media-left">
                    <?php if(empty($profile->path)){ ?>
                    <img src="http://www.lorempixum.com/80/80/people" alt="<?php echo $profile->name; ?> Image" class="profile-pic" />
                    <?php } else { ?>
                    <img src="/uploads/<?php echo $profile->path; ?>" width="80" height="80" alt="<?php echo $profile->name; ?> Image" class="profile-pic" />
                    <?php } ?>
                </div>
                <div class="media-body">
                    <h3 class="media-heading"><?php echo $profile->name; ?></h3>
                </div>
            </div>
        </div>
        */
?>
