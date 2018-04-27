<div class="container" id="admin-dashboard">


    <?php if(isset($response)) { ?>
    <div class="row">
        <div class="col-md-12">
            <?php printResponse($response);  ?>
        </div>
    </div>
    <?php } ?>


    <!-- Profiles -->
    <section class="row profiles">
        <div class="col-md-12">
            <h5><?php _t("Admin Console");?></h5>
        </div>

        <div class="col-md-5">
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
                               <img src="/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg" width="40" height="40" alt="<?php echo $group->name; ?> Image" class="profile-pic clearfix">&nbsp;</img>
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
        <div class="col-md-5">
            <div class="btn-group btn-group-justified">
                <a class="btn btn-default" href="/user/all"><?php _t("Users");?></a>
                <a class="btn btn-default" href="/user/create"><?php _t("Add User");?></a>
            </div>
        </div>
        <div class="col-md-2">
          <a class="btn btn-default btn-block" href="/device"><?php _t("Manage Devices");?></a>
        </div>

    </section>
    <section class="row profiles">
        <div class="col-md-12">
            <div class="row" id="group-main-stats">
                <div class="col">
                    <h5><?php _t("participants");?></h5>
                    <span class="largetext"><?php echo $pax; ?></span>
                </div>

                <div class="col">
                    <h5><?php _t("hours volunteered");?></h5>
                    <span class="largetext"><?php echo $hours; ?></span>
                </div>

                <div class="col">
                    <h5><?php _t("parties thrown");?></h5>
                    <span class="largetext"><?php echo count($allparties); ?></span>
                </div>

                <div class="col">
                    <h5><?php _t("waste prevented");?></h5>
                    <span class="largetext">
                        <?php echo number_format(round($wasteTotal), 0, '.', ','); ?> kg
                    </span>
                </div>

                <div class="col">
                    <h5><?php _t("CO<sub>2</sub> emission prevented");?></h5>

                    <span class="largetext">
                        <?php echo number_format(round($co2Total), 0, '.', ','); ?> kg
                    </span>
                </div>

            </div>
        </div>
        <div class="col-md-12 text-center">
            <a class="btn btn-default" href="/search"><i class="fa fa-filter"></i> <?php _t("Filter Parties");?></a>
        </div>
    </section>




     <!-- Tabs -->
    <!-- Nav tabs -->
    <ul class="nav nav-pills nav-justified" role="tablist">
        <li role="presentation" class="active"><a href="#parties-tab" aria-controls="Parties" role="tab" data-toggle="pill"><?php _t("Parties");?></a></li>
        <li role="presentation"><a href="#impact-tab" aria-controls="Impact" role="tab" data-toggle="pill"><?php _t("Impact");?></a></li>
        <li role="presentation"><a href="#details-tab" aria-controls="Details" role="tab" data-toggle="pill"><?php _t("Details");?></a></li>
    </ul>






    <!-- Tab panes -->
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="parties-tab">
            <!-- Upcoming Parties -->
            <section class="row parties">
                <header>
                <div class="col-md-12" id="upcomingparties">
                    <h2><?php _t("Upcoming Restart Parties");?>
                        <a class="btn btn-primary btn-sm" href="/party/create"><i class="fa fa-plus"></i> <?php _t("New Party");?></a></h2>
                </div>
                </header>
                <?php foreach($upcomingparties as $party){ ?>
                <div class="col-md-6">
                    <div class="media">
                        <div class="media-left">
                            <img class="media-object" alt="The Restart Project: Logo" src="/assets/images/logo_mini.png">
                        </div>
                        <div class="media-body">
                            <div class="body">
                                <time datetime="<?php echo dbDate($party->event_date); ?>"><?php echo strftime('%a, %d %b %Y %H:%M',  $party->event_timestamp); ?></time>
                                <span clasS="location"><?php  echo (!empty($party->venue) ? $party->venue : $party->location); ?></span>
                            </div>
                            <div class="links">
                                <a href="/party/edit/<?php echo $party->idevents; ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-edit"></i> <?php _t("edit");?></a>
                                <a href="/party/delete/<?php echo $party->idevents; ?>" class="btn btn-danger btn-sm btn-block delete-control"><i class="fa fa-trash"></i> <?php _t("delete");?></a>
                            </div>
                        </div>
                    </div>

                </div>
                <?php } ?>
                <div class="col-md-3 col-md-offset-9">
                    <a href="http://www.therestartproject.org/events"><?php _t("view all upcoming events");?></a>
                </div>
            </section>


            <!-- Latest Parties -->
            <section class="row parties">
                <header>
                    <div class="col-md-12"  id="allparties">
                        <h2>
                            <?php _t("All Parties");?> <a href="/party" class="btn btn-primary btn-sm"><?php _t("View/edit all parties");?></a>
                        </h2>
                    </div>
                </header>




                <br />
                <div class="col-md-12" id="party-list-header">
                    <div class="header-col header-col-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary btn-sm party-switch active" data-target="all"><?php _t("All Parties");?></button>
                                <button type="button" class="btn btn-primary btn-sm party-switch" data-target=".no-data-wrap"><?php _t("Need Attention");?></button>
                                <button type="button" class="btn btn-primary btn-sm party-switch" data-target=".guesstimates"><?php _t("Guesstimated");?></button>
                            </div>

                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_pax.png" alt="Participants" class="header-icon">
                        <span class="icon-label"><?php _t("Participants");?></span>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_volunters.png" alt="Restarters" class="header-icon">
                        <span class="icon-label"><?php _t("Restarters");?></span>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_emissions.png" alt="CO2 Emissions Prevented" class="header-icon">
                        <span class="icon-label">CO<sub>2</sub> Emissions prevented</span>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_fixed.png" alt="Fixed" class="header-icon">
                        <span class="icon-label"><?php _t("Fixed");?></span>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_repairable.png" alt="Repairable" class="header-icon">
                        <span class="icon-label"><?php _t("Repairable");?></span>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_dead.png" alt="Dead" class="header-icon">
                        <span class="icon-label"><?php _t("Dead");?></span>
                    </div>

                </div>
                <div class="col-md-12 fader" id="party-list">

                    <?php
                    $nodata = 0;
                    $currentYear = date('Y', time());
                    foreach($allparties as $party){
                        $partyYear = date('Y', $party->event_timestamp);
                        if( $partyYear < $currentYear){
                    ?>
                    <div class="year-break">
                        <?php echo $partyYear; ?>
                    </div>
                    <?php
                            $currentYear = $partyYear;
                        }
                    ?>
                    <?php if($party->device_count < 1){ $nodata++; ?>
                    <a class="no-data-wrap party" href="/party/manage/<?php echo $party->idevents; ?>" <?php echo ($nodata == 1 ? 'id="attention"' : ''); ?>>

                        <div class="header-col-2 header-col">
                            <div class="date">
                                <span class="month"><?php echo date('M', $party->event_timestamp); ?></span>
                                <span class="day">  <?php echo date('d', $party->event_timestamp); ?></span>
                                <span class="year"> <?php echo date('Y', $party->event_timestamp); ?></span>
                            </div>

                            <div class="short-body">
                                <span class="location"><?php  echo (!empty($party->venue) ? $party->venue : $party->location); ?></span>
                                <time datetime="<?php echo dbDate($party->event_date); ?>"><?php echo substr($party->start, 0, -3); ?></time>

                            </div>
                        </div>
                        <div class="header-col header-col-3">
                            <button class="btn btn-primary btn-lg add-info-btn">
                                <i class="fa fa-cloud-upload"></i> <?php _t("Add Information");?>
                            </button>
                        </div>
                        <div class="header-col">
                            <span class="largetext greyed">?</span>
                        </div>

                        <div class="header-col">
                            <span class="largetext greyed">?</span>
                        </div>

                        <div class="header-col">
                            <span class="largetext greyed">?</span>
                        </div>

                    </a>
                    <?php } else {  ?>
                    <a class=" party <?php echo ($party->guesstimates == true ? ' guesstimates' : ''); ?>"  href="/party/manage/<?php echo $party->idevents; ?>">
                        <div class="header-col header-col-2">
                            <div class="date">
                                <span class="month"><?php echo date('M', $party->event_timestamp); ?></span>
                                <span class="day">  <?php echo date('d', $party->event_timestamp); ?></span>
                                <span class="year"> <?php echo date('Y', $party->event_timestamp); ?></span>
                            </div>

                            <div class="short-body">
                                <span class="location"><?php  echo (!empty($party->venue) ? $party->venue : $party->location); ?></span>
                                <time datetime="<?php echo dbDate($party->event_date); ?>"><?php echo  substr($party->start, 0, -3); ?></time>

                            </div>
                        </div>

                        <div class="header-col">
                            <span class="largetext">
                                <?php echo $party->pax; ?>
                            </span>
                        </div>

                        <div class="header-col">
                            <span class="largetext">
                                <?php echo $party->volunteers; ?>
                            </span>
                        </div>

                        <div class="header-col">
                            <span class="largetext">
                                 <?php echo $party->co2; ?> kg
                            </span>
                        </div>

                        <div class="header-col">
                            <span class="largetext fixed">
                                <?php echo $party->fixed_devices; ?>
                            </span>
                        </div>

                        <div class="header-col">
                            <span class="largetext repairable">
                                <?php echo $party->repairable_devices; ?>
                            </span>
                        </div>

                        <div class="header-col">
                            <span class="largetext dead">
                                <?php echo $party->dead_devices; ?>
                            </span>
                        </div>

                    </a>
                    <?php } ?>
                <?php } ?>
                </div>

            </section>


        </div>




        <div role="tabpanel" class="tab-pane" id="impact-tab">
            <section class="row" id="impact-header">
                <div class="col-sm-12 text-center">
                    <img src="/assets/images/logo.png" >
                    <h2>The Restart Project</h2>

                    <p class="big">
                        <span class="big blue"><?php echo $pax; ?> <?php _t("participants");?></span> <?php _t("aided by");?>
                        <span class="big blue"><?php echo $hours; ?> <?php _t("hours of volunteered time");?></span> <?php _t("worked on");?>
                        <span class="big blue"><?php echo ($device_count_status[0]->counter + $device_count_status[1]->counter + $device_count_status[2]->counter) ?> <?php _t("devices.");?></span>
                    </p>

                </div>
            </section>

            <section class="row" id="impact-devices">
                <div class="col-md-6 col-md-offset-3  text-center">

                    <div class="impact-devices-1">
                        <img src="/assets/icons/impact_device_1.jpg" class="" width="200">
                        <span class="title"><?php echo (int)$device_count_status[0]->counter;?></span>
                        <span class="legend"><?php _t("were fixed");?></span>
                    </div>

                    <div class="impact-devices-2">
                        <img src="/assets/icons/impact_device_2.jpg" class="" width="200">
                        <span class="title"><?php echo (int)$device_count_status[1]->counter;?></span>
                        <span class="legend"><?php _t("were still repairable");?></span>
                    </div>

                    <div class="impact-devices-3">
                        <img src="/assets/icons/impact_device_3.jpg" class="" width="200">
                        <span class="title"><?php echo (int)$device_count_status[2]->counter;?></span>
                        <span class="legend"><?php _t("were dead");?></span>
                    </div>

                </div>

                <div class="col-md-12">
                    <h2><span class="title-text"><?php _t("Most Repaired Devices");?></span></h2>

                    <div class="row">
                        <div class="col-md-4"><div class="topper  text-center"><?php echo $top[0]->name . ' [' . $top[0]->counter . ']'; ?></div></div>
                        <div class="col-md-4"><div class="topper  text-center"><?php echo $top[1]->name . ' [' . $top[1]->counter . ']'; ?></div></div>
                        <div class="col-md-4"><div class="topper  text-center"><?php echo $top[2]->name . ' [' . $top[2]->counter . ']'; ?></div></div>
                    </div>
                </div>

            </section>

            <section class="row" id="impact-dataviz">
                <div class="col-md-12 text-center texter">
                    <span class="datalabel"><?php _t("Total waste prevented:");?> </span><span class="blue">  <?php echo number_format(round($wasteTotal), 0, '.', ','); ?> kg </span>
                </div>
                <div class="col-md-12 text-center texter">
                    <span class="datalabel"><?php _t("Total CO<sub>2</sub> emission prevented:");?> </span><span class="blue"><?php echo number_format(round($co2Total), 0, '.', ','); ?> kg</span>
                </div>
                <div class="col-md-12">
                    <?php
                    /** find size of needed SVGs **/
                    if($co2Total > 6000) {
                        $consume_class = 'car';
                        $consume_image = 'Counters_C2_Driving.svg';
                        $consume_label = 'Equal to driving';
                        $consume_eql_to = (1 / 0.12) * $co2Total;
                        $consume_eql_to = number_format(round($consume_eql_to), 0, '.', ',') . '<small>km</small>';

                        $manufacture_eql_to = round($co2Total / 6000);
                        $manufacture_img = 'Icons_04_Assembly_Line.svg';
                        $manufacture_label = 'or like the manufacture of <span class="dark">' . $manufacture_eql_to . '</span> cars';
                        $manufacture_legend = ' 6000kg of CO<sub>2</sub>';
                    }
                    else {
                        $consume_class = 'tv';
                        $consume_image = 'Counters_C1_TV.svg';
                        $consume_label = 'Like watching TV for';
                        $consume_eql_to = ((1 / 0.024) * $co2Total) / 24;
                        $consume_eql_to = number_format(round($consume_eql_to), 0, '.', ',') . '<small>days</small>';

                        $manufacture_eql_to = round($co2Total / 100);
                        $manufacture_img = 'Icons_03_Sofa.svg';
                        $manufacture_label = 'or like the manufacture of <span class="dark">' . $manufacture_eql_to . '</span> sofas';
                        $manufacture_legend = ' 100kg of CO<sub>2</sub>';
                    }
                    ?>

                    <div class="di_consume <?php echo $consume_class; ?>">
                        <img src="/assets/icons/<?php echo $consume_image; ?>" class="img-responsive">
                        <div class="text">
                            <div class="blue"><?php _t($consume_label); ?></div>
                            <div class="consume"><?php echo $consume_eql_to; ?></div>
                        </div>
                    </div>

                    <div class="di_manufacture">
                        <div class="col-md-12 text-center"><div class="lightblue"><?php echo $manufacture_label; ?></div></div>
                        <?php for($i = 1; $i<= $manufacture_eql_to; $i++){ ?>
                            <div class="col-md-3 text-center">
                                <img src="/assets/icons/<?php echo $manufacture_img; ?>" class="img-responsive">
                            </div>
                        <?php } ?>
                        <div class="col-md-12 text-center">
                            <div class="legend">1 <img src="/assets/icons/<?php echo $manufacture_img; ?>"> = <?php echo $manufacture_legend; ?> (<?php _t("approximately");?>)</div>

                        </div>
                    </div>


                </div>

            </section>


        </div>

        <div role="tabpanel" class="tab-pane" id="details-tab">



            <!-- Group Achievements -->
            <section class="row">
                <div class="col-md-12">
                    <h2>Restart Project <?php _t("Achievements");?></h2>
                </div>
            </section>

            <!-- CO2 stats -->
            <section class="row">
                <div class="col-md-12">
                    <h3>Impact</h3>
                </div>

                <div class="col-md-6">
                    <h5 class="text-center"><?php _t("e-Waste Prevented to date");?></h5>
                    <span class="largetext">
                        <?php echo number_format(round($wasteTotal), 0, '.', ','); ?> kg
                    </span>

                    <hr />

                    <h5 class="text-center"><?php _t("e-Waste prevented this year");?></h5>
                    <?php

                        foreach($waste_year_data as $y){
                            if($y->year == date('Y', time())) {
                    ?>
                    <span class="largetext">
                        <?php echo number_format(round($y->waste), 0, '.', ','); ?> kg
                    </span>
                    <?php
                            }
                        }
                    ?>

                </div>

                <div class="col-md-6">
                    <h5 class="text-center">CO<sub>2</sub><?php _t("emission prevented to date");?></h5>

                    <span class="largetext">
                        <?php echo number_format(round($co2Total), 0, '.', ','); ?> kg of CO<sub>2</sub>
                    </span>

                    <hr />

                    <h5 class="text-center">CO<sub>2</sub> <?php _t("emission prevented this year");?></h5>
                    <span class="largetext">
                        <?php echo number_format(round($co2ThisYear), 0, '.', ','); ?> kg of CO<sub>2</sub>
                    </span>


                </div>


            </section>

            <hr />


            <section class="row">
                <!-- Device count -->

                <div class="col-md-12">
                    <h3><?php _t("Devices Restarted");?></h3>
                    <div class="row">
                        <div class="col-md-4 count">
                            <div class="col">
                                <img src="/assets/icons/fixed_circle.jpg">
                            </div>
                            <div class="col">
                                <span class="status_title"><?php _t("Fixed");?></span>
                                <span class="largetext fixed">
                                    <?php echo $device_count_status[0]->counter; ?>
                                </span>

                            </div>
                        </div>
                        <div class="col-md-4 count">
                            <div class="col repairable">
                                <img src="/assets/icons/repairable_circle.jpg">
                            </div>
                            <div class="col">
                                <span class="status_title"><?php _t("Repairable");?></span>
                                <span class="largetext repairable">

                                    <?php echo $device_count_status[1]->counter; ?>
                                </span>

                            </div>
                        </div>
                        <div class="col-md-4 count">
                            <div class="col dead">
                                <img src="/assets/icons/dead_circle.jpg">
                            </div>
                            <div class="col">
                                <span class="status_title"><?php _t("Dead");?></span>
                                <span class="largetext dead">
                                    <?php echo $device_count_status[2]->counter; ?>
                                </span>

                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <hr />



            <!-- category details -->
            <section class="row">
                <div class="col-md-12">
                    <h3><?php _t("Category Details");?></h3>
                </div>

                <div class="row">
                    <div class="col-md-2">&nbsp;</div>
                    <div class="col-md-4">
                        <div class="col3">
                            <img src="/assets/icons/icon_fixed.png" title="fixed items" alt="Fixed Items icon">
                            <span class="subtext"><?php _t("fixed");?></span>
                        </div>
                        <div class="col3 no-brd">
                            <img src="/assets/icons/icon_repairable.png" title="repairable items" alt="repairable Items icon">
                            <span class="subtext"><?php _t("repairable");?></span>
                        </div>
                        <div class="col3">
                            <img src="/assets/icons/icon_dead.png" title="dead items" alt="dead Items icon">
                            <span class="subtext"><?php _t("dead");?></span>
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-2  text-center">
                        <i class="cluster big cluster-1"></i>
                    </div>
                    <div class="col-md-4">
                        <div class="col3">
                            <span class="largetext fixed"><?php echo $clusters['all'][1][0]->counter; ?></span>
                        </div>
                        <div class="col3">

                            <span class="largetext repairable"><?php echo $clusters['all'][1][1]->counter; ?></span>
                        </div>
                        <div class="col3">

                            <span class="largetext dead"><?php echo $clusters['all'][1][2]->counter; ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">

                        <div class="category-detail">
                            <table cellspacing="0">
                                <thead>
                                    <tr>
                                        <th colspan="3">
                                            <?php _t("Computers and Home Office");?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="table-label"><?php _t("Most seen:");?></td>
                                        <td class="table-data"><?php echo $mostleast[1]['most_seen'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[1]['most_seen'][0]->counter; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="table-label"><?php _t("Most repaired:");?></td>
                                        <td class="table-data"><?php echo $mostleast[1]['most_repaired'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[1]['most_repaired'][0]->counter; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="table-label"><?php _t("Least repaired:");?></td>
                                        <td class="table-data"><?php echo $mostleast[1]['least_repaired'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[1]['least_repaired'][0]->counter; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>


                    </div>
                </div>


                <div class="row">
                    <div class="col-md-2  text-center">
                        <i class="cluster big cluster-2"></i>

                    </div>
                    <div class="col-md-4">
                        <div class="col3">

                            <span class="largetext fixed"><?php echo $clusters['all'][2][0]->counter; ?></span>
                        </div>
                        <div class="col3">

                            <span class="largetext repairable"><?php echo $clusters['all'][2][1]->counter; ?></span>
                        </div>
                        <div class="col3">

                            <span class="largetext dead"><?php echo $clusters['all'][2][2]->counter; ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="category-detail">
                            <table cellspacing="0">
                                <thead>
                                    <tr>
                                        <th colspan="3">
                                            <?php _t("Electronic Gadgets");?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="table-label"><?php _t("Most seen:");?></td>
                                        <td class="table-data"><?php echo $mostleast[2]['most_seen'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[2]['most_seen'][0]->counter; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="table-label"><?php _t("Most repaired:");?></td>
                                        <td class="table-data"><?php echo $mostleast[2]['most_repaired'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[2]['most_repaired'][0]->counter; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="table-label"><?php _t("Least repaired:");?></td>
                                        <td class="table-data"><?php echo $mostleast[2]['least_repaired'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[2]['least_repaired'][0]->counter; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>


                    </div>
                </div>

                <div class="row">
                    <div class="col-md-2  text-center">
                        <i class="cluster big cluster-3"></i>

                    </div>
                    <div class="col-md-4">
                        <div class="col3">

                            <span class="largetext fixed"><?php echo $clusters['all'][3][0]->counter; ?></span>
                        </div>
                        <div class="col3">

                            <span class="largetext repairable"><?php echo $clusters['all'][3][1]->counter; ?></span>
                        </div>
                        <div class="col3">

                            <span class="largetext dead"><?php echo $clusters['all'][3][2]->counter; ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="category-detail">
                            <table cellspacing="0">
                                <thead>
                                    <tr>
                                        <th colspan="3">
                                            <?php _t("Home Entertainment");?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="table-label"><?php _t("Most seen:");?></td>
                                        <td class="table-data"><?php echo $mostleast[3]['most_seen'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[3]['most_seen'][0]->counter; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="table-label"><?php _t("Most repaired:");?></td>
                                        <td class="table-data"><?php echo $mostleast[3]['most_repaired'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[3]['most_repaired'][0]->counter; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="table-label"><?php _t("Least repaired:");?></td>
                                        <td class="table-data"><?php echo $mostleast[3]['least_repaired'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[3]['least_repaired'][0]->counter; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2 text-center">
                        <i class="cluster big cluster-4"></i>

                    </div>
                    <div class="col-md-4">
                        <div class="col3">

                            <span class="largetext fixed"><?php echo $clusters['all'][4][0]->counter; ?></span>
                        </div>
                        <div class="col3">

                            <span class="largetext repairable"><?php echo $clusters['all'][4][1]->counter; ?></span>
                        </div>
                        <div class="col3">

                            <span class="largetext dead"><?php echo $clusters['all'][4][2]->counter; ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="category-detail">
                            <table cellspacing="0">
                                <table cellspacing="0">
                                <thead>
                                    <tr>
                                        <th colspan="3">
                                            <?php _t("Kitchen and Household Items");?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="table-label"><?php _t("Most seen:");?></td>
                                        <td class="table-data"><?php echo $mostleast[4]['most_seen'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[4]['most_seen'][0]->counter; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="table-label"><?php _t("Most repaired:");?></td>
                                        <td class="table-data"><?php echo $mostleast[4]['most_repaired'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[4]['most_repaired'][0]->counter; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="table-label"><?php _t("Least repaired:");?></td>
                                        <td class="table-data"><?php echo $mostleast[4]['least_repaired'][0]->name; ?></td>
                                        <td class="table-count"><?php echo $mostleast[4]['least_repaired'][0]->counter; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>


            </section>

            <!--categories-->
            <section class="row">
                <div class="col-md-12">
                    <h3><?php _t("Devices Restarted per Category");?></h3>
                </div>
                <?php
                //dbga($clusters);
                $c = 1;
                foreach($clusters as $key => $cluster){ ?>
                <div class="col-md-10 <?php echo($c == 1 ? 'show' : 'hide'); ?> bargroup" id="<?php echo $key; ?>">

                    <div class="row">
                        <div class="col-md-2">
                            <span class="cluster big cluster-1"></span>
                        </div>
                        <div class="col-md-4">
                            <div class="barpiece fixed" style="width :<?php echo round((($cluster[1][0]->counter / $cluster[1]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo round((($cluster[1][0]->counter / $cluster[1]['total']) * 100) , 2); ?>%</div>
                            <div class="barpiece repairable" style="width :<?php echo round((($cluster[1][1]->counter / $cluster[1]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo round((($cluster[1][1]->counter / $cluster[1]['total']) * 100) , 2); ?>%</div>
                            <div class="barpiece end-of-life" style="width :<?php echo round((($cluster[1][2]->counter / $cluster[1]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo round((($cluster[1][2]->counter / $cluster[1]['total']) * 100) , 2); ?>%</div>
                        </div>


                        <div class="col-md-2">
                            <span class="cluster big cluster-2"></span>
                        </div>
                        <div class="col-md-4">
                            <div class="barpiece fixed" style="width :<?php echo round((($cluster[2][0]->counter / $cluster[2]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo round((($cluster[2][0]->counter / $cluster[2]['total']) * 100) , 2); ?>%</div>
                            <div class="barpiece repairable" style="width :<?php echo round((($cluster[2][1]->counter / $cluster[2]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo round((($cluster[2][1]->counter / $cluster[2]['total']) * 100) , 2); ?>%</div>
                            <div class="barpiece end-of-life" style="width :<?php echo round((($cluster[2][2]->counter / $cluster[2]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo round((($cluster[2][2]->counter / $cluster[2]['total']) * 100) , 2); ?>%</div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-2">
                            <span class="cluster big cluster-3"></span>
                        </div>
                        <div class="col-md-4">
                            <div class="barpiece fixed" style="width :<?php echo round((($cluster[3][0]->counter / $cluster[3]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo round((($cluster[3][0]->counter / $cluster[3]['total']) * 100) , 2); ?>%</div>
                            <div class="barpiece repairable" style="width :<?php echo round((($cluster[3][1]->counter / $cluster[3]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo round((($cluster[3][1]->counter / $cluster[3]['total']) * 100) , 2); ?>%</div>
                            <div class="barpiece end-of-life" style="width :<?php echo round((($cluster[3][2]->counter / $cluster[3]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo round((($cluster[3][2]->counter / $cluster[3]['total']) * 100) , 2); ?>%</div>
                        </div>

                        <div class="col-md-2">
                            <span class="cluster big cluster-4"></span>
                        </div>
                        <div class="col-md-4">
                            <div class="barpiece fixed" style="width :<?php echo round((($cluster[4][0]->counter / $cluster[4]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo round((($cluster[4][0]->counter / $cluster[4]['total']) * 100) , 2); ?>%</div>
                            <div class="barpiece repairable" style="width :<?php echo round((($cluster[4][1]->counter / $cluster[4]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo round((($cluster[4][1]->counter / $cluster[4]['total']) * 100) , 2); ?>%</div>
                            <div class="barpiece end-of-life" style="width :<?php echo round((($cluster[4][2]->counter / $cluster[4]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo round((($cluster[4][2]->counter / $cluster[4]['total']) * 100) , 2); ?>%</div>
                        </div>


                    </div>


                </div>
                <?php
                    $c++;
                }
                ?>

                <div class="col-md-2">
                    <div class=" barpiece-tabs">
                        <ul>
                        <?php
                        $c = 1;
                        foreach($clusters as $key => $cluster){
                        ?>
                            <li><button class="btn btn-primary btn-sm <?php echo($c == 1 ? 'active' : ''); ?> switch-view" data-family=".bargroup" data-target="#<?php echo $key; ?>"><?php echo strtoupper($key); ?></button></li>
                        <?php
                        $c++;
                        }
                        ?>


                        </ul>
                    </div>

                </div>
            </section>


        </div>


        </div>

</div>
