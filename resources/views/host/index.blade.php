@extends('layouts.app')

@section('content')
<div class="container" id="host-dashboard">


    @if(isset($response))
    <div class="row">
        <div class="col-md-12">
            @php( printResponse($response) )
        </div>
    </div>
    @endif


    <!-- Profiles -->
    @if(FixometerHelper::hasRole( $user, 'Administrator' ))


    <section class="row profiles">
        <div class="col-md-12">
            <h5>Admin Console</h5>
        </div>
        <div class="col-md-6">
            <div class="btn-group btn-group-justified">


                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Groups
                      <span class="fa fa-chevron-down"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @foreach($grouplist as $g)
                        <li class="group-list clearfix">
                            <div class="pull-left">
                                @if(!empty($g->path))
                                <img src="/uploads/thumbnail_<?php echo $g->path; ?>" width="40" height="40" alt="<?php echo $g->name; ?> Image" class="profile-pic" />
                                @else
                                <img src="/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg" width="40" height="40" alt="<?php echo $group->name; ?> Image" class="profile-pic clearfix"/>
                                @endif
                            </div>
                            <div class="pull-left">
                                <a  href="/host/index/<?php echo $g->id; ?>" ><?php echo $g->name; ?></a>
                            </div>
                        </li>

                        @endforeach
                    </ul>
                </div>

                <a class="btn btn-default" href="/group/create">Add Group</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="btn-group btn-group-justified">
                <a class="btn btn-default" href="/user/all">Users</a>
                <a class="btn btn-default" href="/user/create">Add User</a>
            </div>
        </div>

    </section>



    @endif
    @if (FixometerHelper::hasRole($user, 'Host') && count($userGroups) > 0)
      <section class="row profiles">
        <div class="col-md-12">
          <p>You are a host of multiple groups.  You can switch to a different group by clicking the button(s) below.</p>
          @foreach($userGroups as $g) {
            @if($group->idgroups != $g->idgroups)
              <a class="btn btn-primary" href="/host/index/<?php echo $g->idgroups; ?>"><img class="img-responsive pull-left" width="20" height="20" alt="<?php echo $g->name; ?>" src="/uploads/thumbnail_<?php echo $g->path; ?>">&nbsp;&nbsp;Switch to "<?php echo $g->name; ?>"</a>
            @endif
          @endforeach
        </div>
      </section>
    @endif

    <section class="row profiles" id="group-profile">
        <div class="col-md-3">
            <div class="media">
                <h3 class="media-heading"><?php echo $group->name; ?></h3>

                <div class="media-left">
                    @if(empty($group->path))
                    <img src="/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg" width="60" height="60" alt="<?php echo $group->name; ?> Image" class="profile-pic" />
                    @else
                    <img src="/uploads/thumbnail_<?php echo $group->path; ?>" width="60" height="60" alt="<?php echo $group->name; ?> Image" class="profile-pic" />
                    @endif
                </div>
                <div class="media-body">

                    <span><?php echo $group->location . (!empty($group->area) ? ', ' . $group->area : ''); ?></span><br />
                    <a href="/group/edit/<?php echo $group->idgroups; ?>" class="small"><i class="fa fa-edit"></i>Edit Group...</a>

                </div>
            </div>
        </div>


        <div class="col-md-9">
            <div class="row" id="group-main-stats">
                <div class="col">
                    <h5>participants</h5>
                    <span class="largetext"><?php echo $pax; ?></span>
                </div>

                <div class="col">
                    <h5>hours volunteered</h5>
                    <span class="largetext"><?php echo $hours; ?></span>
                </div>

                <div class="col">
                    <h5>parties thrown</h5>
                    <span class="largetext"><?php echo count($allparties); ?></span>
                </div>

                <div class="col">
                    <h5>waste prevented</h5>

                    <span class="largetext">
                        <?php echo $weights[0]->total_weights; ?> kg
                    </span>
                </div>

                <div class="col">
                    <h5>CO<sub>2</sub> emission prev

                    <span class="largetext"><?php echo $weights[0]->total_footprints; ?> kg</span>
                </div>

            </div>
        </div>

        <div class="col-md-12 text-right">
          <a class="btn btn-default" href="/search"><i class="fa fa-filter"></i>Filter Parties</a>
          <button class="btn btn-default" type="button" data-toggle="modal" data-target="#esw"><i class="fa fa-share"></i>Share your stats</button>
        </div>
    </section>




    <!-- Tabs -->
    <!-- Nav tabs -->
    <ul class="nav nav-pills nav-justified" role="tablist">
        <li role="presentation" class="active"><a href="#parties-tab" aria-controls="Parties" role="tab" data-toggle="pill">Parties</a></li>
        <li role="presentation"><a href="#impact-tab" aria-controls="Impact" role="tab" data-toggle="pill">Impact</a></li>
        <li role="presentation"><a href="#details-tab" aria-controls="Details" role="tab" data-toggle="pill">Details</a></li>
    </ul>



    <!-- Tab panes -->
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="parties-tab">
            <section class="row parties">
                <header>
                <div class="col-md-12" id="upcomingparties">
                    <h2>
                        <span class="title-text">Upcoming Restart Parties<a href="/party/create" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i>New Party</a></span>

                    </h2>
                </div>
                </header>


                @if(empty($upcomingparties))
                <div class="col-md-12"><p class="text-center">No Upcoming Parties. <a href="/party/create">Add a party.</a></p></div>
                @else

                @foreach($upcomingparties as $party)
                <div class="col-md-6">
                    <div class="media">
                        <div class="media-left">
                            <img class="media-object" alt="The Restart Project: Logo" src="/assets/images/logo_mini.png">
                        </div>
                        <div class="media-body">
                            <div class="body">
                                <time datetime="<?php echo $party->event_date; ?>"><?php echo strftime('%a, %d %b %Y %H:%M',  $party->event_timestamp); ?></time>
                                <span clasS="location"><?php echo (!empty($party->venue) ? $party->venue : $party->location); ?></span>
                            </div>
                            <div class="links">
                                <a href="/party/edit/<?php echo $party->idevents; ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-edit"></i> edit</a>
                                <a href="/party/delete/<?php echo $party->idevents; ?>" class="btn btn-danger btn-sm btn-block delete-control"><i class="fa fa-trash"></i> delete</a>
                            </div>
                        </div>
                    </div>

                </div>
                @endforeach
                <div class="col-md-3 col-md-offset-9">

                </div>
                @endif
            </section>


            <!-- Latest Parties -->
            <section class="row parties">
                <header>
                    <div class="col-md-12"  id="allparties">
                        <h2>
                           <span class="title-text">All <span class="grey"><?php echo $group->name; ?></span> Parties</span>
                        </h2>
                    </div>
                </header>

                <div class="col-md-12" id="party-list-header">
                    <div class="header-col header-col-2" id="header-link-box">
                        <a href="http://www.therestartproject.org/party">view all upcoming events</a>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_pax.png" alt="Participants" class="header-icon">
                        <span class="icon-label">Participants</span>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_volunters.png" alt="Restarters" class="header-icon">
                        <span class="icon-label">Restarters</span>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_emissions.png" alt="CO2 Emissions Prevented" class="header-icon">
                        <span class="icon-label">CO<sub>2</sub>Emissions prevented</span>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_fixed.png" alt="Fixed" class="header-icon">
                        <span class="icon-label">Fixed</span>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_repairable.png" alt="Repairable" class="header-icon">
                        <span class="icon-label">Repairable</span>
                    </div>

                    <div class="header-col">
                        <img src="/assets/icons/icon_dead.png" alt="Dead" class="header-icon">
                        <span class="icon-label">Dead</span>
                    </div>

                </div>

                <div class="col-md-12 well" id="party-list">

                    <?php
                    $nodata = 0;
                    $currentYear = date('Y', time());
                    ?>
                    @foreach($allparties as $party)
                    @php( $partyYear = date('Y', $party->event_timestamp) )
                        @if( $partyYear < $currentYear)
                    <div class="year-break">
                        <?php echo $partyYear; ?>
                    </div>
                            @php( $currentYear = $partyYear )
                        @endif
                    @if($party->device_count < 1)
                      @php( $nodata++ )
                    <a class="no-data-wrap party" href="/party/manage/<?php echo $party->idevents; ?>" <?php echo ($nodata == 1 ? 'id="attention"' : ''); ?>>

                        <div class="header-col header-col-2">
                            <div class="date">
                                <span class="month"><?php echo strftime('%b', $party->event_timestamp); ?></span>
                                <span class="day">  <?php echo strftime('%d', $party->event_timestamp); ?></span>
                                <span class="year"> <?php echo strftime('%Y', $party->event_timestamp); ?></span>
                            </div>

                            <div class="short-body">
                                <span class="location"><?php echo (!empty($party->venue) ? $party->venue : $party->location); ?></span>
                                <time datetime="<?php echo $party->event_date; ?>"><?php echo substr($party->start, 0, -3); ?></time>
                            </div>
                        </div>

                        <div class="header-col header-col-3">
                            <button class="btn btn-primary btn-lg add-info-btn">
                                <i class="fa fa-cloud-upload"></i>Add Information
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
                    @else
                    <a class="party"  href="/party/manage/<?php echo $party->idevents; ?>">
                        <div class="header-col header-col-2">
                            <div class="date">
                                <span class="month"><?php echo date('M', $party->event_timestamp); ?></span>
                                <span class="day">  <?php echo date('d', $party->event_timestamp); ?></span>
                                <span class="year"> <?php echo date('Y', $party->event_timestamp); ?></span>
                            </div>

                             <div class="short-body">
                                <span class="location"><?php echo (!empty($party->venue) ? $party->venue : $party->location); ?></span>
                                <time datetime="<?php echo $party->event_date; ?>"><?php echo  substr($party->start, 0, -3); ?></time>
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
                    @endif
                @endforeach
                </div>

            </section>


        </div>

        <div role="tabpanel" class="tab-pane" id="impact-tab">

            <section class="row" id="impact-header">
                <div class="col-sm-12 text-center">
                    @if(!empty($group->path))
                    <img src="/uploads/mid_<?php echo $group->path; ?>" class="img-circle impact-avatar" width="120" />
                    @else
                    <img src="/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg" class="img-circle impact-avatar" width="120"/>
                    @endif
                    <h2><?php echo $group->name; ?></h2>

                    <p class="big">
                        @if( !empty($group_device_count_status) )
                          <span class="big blue"><?php echo $pax; ?> participants</span> aided by <span class="big blue"><?php echo $hours; ?>hours of volunteered time</span> worked on <span class="big blue"><?php echo ($group_device_count_status[0]->counter + $group_device_count_status[1]->counter + $group_device_count_status[2]->counter) ?> devices.</span>
                        @else
                          <span class="big blue"><?php echo $pax; ?> participants</span> aided by <span class="big blue"><?php echo $hours; ?>hours of volunteered time</span> worked on <span class="big blue">0 devices.</span>
                        @endif
                    </p>

                </div>
            </section>

            <section class="row" id="impact-devices">
                <div class="col-md-6 col-md-offset-3  text-center">

                    <div class="impact-devices-1">
                        <img src="/assets/icons/impact_device_1.jpg" class="" width="200">
                        @if ( isset( $group_device_count_status[0] ) )
                          <span class="title"><?php echo (int)$group_device_count_status[0]->counter;?></span>
                        @else
                          <span class="title">0</span>
                        @endif
                        <span class="legend">were fixed</span>
                    </div>

                    <div class="impact-devices-2">
                        <img src="/assets/icons/impact_device_2.jpg" class="" width="200">
                        @if ( isset( $group_device_count_status[1] ) )
                          <span class="title"><?php echo (int)$group_device_count_status[1]->counter;?></span>
                        @else
                          <span class="title">0</span>
                        @endif
                        <span class="legend">were still repairable</span>
                    </div>

                    <div class="impact-devices-3">
                        <img src="/assets/icons/impact_device_3.jpg" class="" width="200">
                        @if ( isset( $group_device_count_status[1] ) )
                          <span class="title"><?php echo (int)$group_device_count_status[2]->counter;?></span>
                        @else
                          <span class="title">0</span>
                        @endif
                        <span class="legend">were dead</span>
                    </div>

                </div>

                <div class="col-md-12">
                    <h2><span class="title-text">Most Repaired Devices</span></h2>

                    <div class="row">
                        @for ($i=0; $i < 3; $i++)
                          @if (isset($top[$i]))
                            <div class="col-md-4"><div class="topper  text-center"><?php echo $top[$i]->name . ' [' . $top[$i]->counter . ']'; ?></div></div>
                          @else
                            <div class="col-md-4"><div class="topper  text-center">N/A</div></div>
                          @endif
                        @endfor
                    </div>
                </div>

            </section>

            <section class="row" id="impact-dataviz">
                <div class="col-md-12 text-center texter">
                    @php( $sum = 0 )
                        @foreach($waste_year_data as $y)
                            @php( $sum += $y->waste )
                        @endforeach
                    <span class="datalabel">Total waste prevented:</span><span class="blue">  <?php echo $weights[0]->total_weights; ?> kg </span>

                </div>
                <div class="col-md-12 text-center texter">
                    @php( $sum = 0 )
                        @foreach($year_data as $y)
                            @php( $sum += $y->co2 )
                        @endforeach
                        <!-- //$di_co2 = number_format(round($sum), 0, '.', ','); -->
                    <span class="datalabel">Total CO<sub>2</sub> emission prevented: </span><span class="blue"><?php echo $weights[0]->total_footprints; ?> kg</span>

                </div>
                <div class="col-md-12">
                    <?php
                    /** find size of needed SVGs **/
                    if($sum > 6000) {
                        $consume_class = 'car';
                        $consume_image = 'Counters_C2_Driving.svg';
                        $consume_label = 'Equal to driving';
                        $consume_eql_to = (1 / 0.12) * $weights[0]->total_footprints;
                        $consume_eql_to = number_format(round($consume_eql_to), 0, '.', ',') . '<small>km</small>';

                        $manufacture_eql_to = round($weights[0]->total_footprints / 6000);
                        $manufacture_img = 'Icons_04_Assembly_Line.svg';
                        $manufacture_label = 'or like the manufacture of <span class="dark">' . $manufacture_eql_to . '</span> cars';
                        $manufacture_legend = ' 6000kg of CO<sub>2</sub>';
                    }
                    else {
                        $consume_class = 'tv';
                        $consume_image = 'Counters_C1_TV.svg';
                        $consume_label = 'Like watching TV for';
                        $consume_eql_to = ((1 / 0.024) * $weights[0]->total_footprints ) / 24;
                        $consume_eql_to = number_format(round($consume_eql_to), 0, '.', ',') . '<small>days</small>';

                        $manufacture_eql_to = round($weights[0]->total_footprints / 100);
                        $manufacture_img = 'Icons_03_Sofa.svg';
                        $manufacture_label = 'or like the manufacture of <span class="dark">' . $manufacture_eql_to . '</span> sofas';
                        $manufacture_legend = ' 100kg of CO<sub>2</sub>';
                    }
                    ?>

                    <div class="di_consume <?php echo $consume_class; ?>">
                        <img src="/assets/icons/<?php echo $consume_image; ?>" class="img-responsive">
                        <div class="text">
                            <div class="blue"><?php echo $consume_label; ?></div>
                            <div class="consume"><?php echo $consume_eql_to; ?></div>
                        </div>
                    </div>

                    <div class="di_manufacture">
                        <div class="col-md-12 text-center"><div class="lightblue"><?php echo $manufacture_label; ?></div></div>
                        @for($i = 1; $i<= $manufacture_eql_to; $i++)
                            <div class="col-md-3 text-center">
                                <img src="/assets/icons/<?php echo $manufacture_img; ?>" class="img-responsive">
                            </div>
                        @endfor
                        <div class="col-md-12 text-center">
                            <div class="legend">1 <img src="/assets/icons/<?php echo $manufacture_img; ?>"> = <?php echo $manufacture_legend; ?> (approximately)</div>

                        </div>
                    </div>

                    <div class="col-md-12 text-right">
                      <button class="btn btn-default" type="button" data-toggle="modal" data-target="#esw"><i class="fa fa-share"></i>Share your stats</button>
                    </div>
                </div>

            </section>


        </div>

        <div role="tabpanel" class="tab-pane" id="details-tab">
            <!-- Group Achievements -->
            <!--<section class="row">
            <div class="col-md-12">
                <h2>
                       <span class="title-text">Group Achievements</span>
                    </h2>
            </div>
            </section>-->

            <!-- CO2 stats -->
            <section class="row parties">
            <div class="col-md-12">
                 <h2><span class="title-text">Impact</span></h2>
            </div>

            <div class="col-md-6">
                <h5 class="text-center">e-Waste Prevented to date</h5>

                <span class="largetext">
                    <?php echo $weights[0]->total_weights; ?> kg
                </span>
                <span class="subtext text-center">Total: <?php echo number_format(round($wasteTotal), 0, '.', ','); ?> kg</span>
                <hr />

                <h5 class="text-center">e-Waste prevented this year</h5>
                @foreach($waste_year_data as $y)
                    @if($y->year == date('Y', time()))
                      <span class="largetext">
                          <?php echo number_format(round($y->waste), 0, '.', ','); ?> kg
                      </span>
                      <span class="subtext text-center">Total: <?php echo number_format(round($wasteThisYear), 0, '.', ','); ?> kg</span>
                    @endif
                @endforeach

            </div>

            <div class="col-md-6">
                <h5 class="text-center">CO<sub>2</sub> emission prevented to date</h5>
                @php( $sum = 0 )
                    @foreach($year_data as $y)
                        @php( $sum += $y->co2 )
                    @endforeach
                <span class="largetext">
                    <?php echo number_format(round($sum), 0, '.', ','); ?> kg of CO<sub>2</sub>
                </span>
                <span class="subtext text-center">Total: <?php echo number_format(round($co2Total), 0, '.', ','); ?> kg of CO<sub>2</sub></span>

                <hr />

                <h5 class="text-center">CO<sub>2</sub> emission prevented this year</h5>
                @foreach($year_data as $y)
                    @if($y->year == date('Y', time()))
                      <span class="largetext">
                          <?php echo number_format(round($y->co2), 0, '.', ','); ?> kg of CO<sub>2</sub>
                      </span>
                      <span class="subtext text-center">Total: <?php echo number_format(round($co2ThisYear), 0, '.', ','); ?>kg of CO<sub>2</sub></span>
                    @endif
                @endforeach

            </div>


            </section>



            <section class="row">
            <!-- Device count -->

            <div class="col-md-12">
                <h2><span class="title-text">Devices Restarted</span></h2>
                <div class="row">
                    <div class="col-md-4 count">
                        <div class="col">
                            <img src="/assets/icons/fixed_circle.jpg">
                        </div>
                        <div class="col">
                            <span class="status_title">Fixed</span>
                            <span class="largetext fixed">
                                @if (isset($group_device_count_status[0]))
                                  <?php echo (int)$group_device_count_status[0]->counter; ?>
                                @else
                                  0
                                @endif
                            </span>
                            <span class="subtext textblue">total: <?php echo $device_count_status[0]->counter; ?></span>
                        </div>
                    </div>

                    <div class="col-md-4 count">
                        <div class="col repairable">
                            <img src="/assets/icons/repairable_circle.jpg">
                        </div>
                        <div class="col">
                            <span class="status_title">Repairable</span>
                            <span class="largetext repairable">
                                @if (isset($group_device_count_status[1]))
                                  <?php echo (int)$group_device_count_status[1]->counter; ?>
                                @else
                                  0
                                @endif
                            </span>
                            <span class="subtext textblue">total: <?php echo $device_count_status[1]->counter; ?></span>
                        </div>
                    </div>


                    <div class="col-md-4 count">
                        <div class="col dead">
                            <img src="/assets/icons/dead_circle.jpg">
                        </div>
                        <div class="col">
                            <span class="status_title">Dead</span>
                            <span class="largetext dead">
                                @if (isset($group_device_count_status[2]))
                                  <?php echo (int)$group_device_count_status[2]->counter; ?>
                                @else
                                  0
                                @endif
                            </span>
                            <span class="subtext textblue">total: <?php echo $device_count_status[2]->counter; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            </section>




            <!-- category details -->
            <section class="row">
            <div class="col-md-12">
                <h2><span class="title-text">Category Details</span></h2>
            </div>

            <div class="row">
                <div class="col-md-2">&nbsp;</div>
                <div class="col-md-4 category-table">
                    <div class="col3">
                        <img src="/assets/icons/icon_fixed.png" title="fixed items" alt="Fixed Items icon">
                        <span class="subt</span>
                    </div>
                    <div class="col3 no-brd">
                        <img src="/assets/icons/icon_repairable.png" title="repairable items" alt="repairable Items icon">
                        <span class="subtext"></span>
                    </div>
                    <div class="col3">
                        <img src="/assets/icons/icon_dead.png" title="dead items" alt="dead Items icon">
                        <span class="sub</span>
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-md-2  text-center">
                    <img class="cluster big img-responsive" src="/assets/icons/Category_Icons-01.png">
                    <!-- <i class="cluster big cluster-1"></i>                -->
                </div>
                <div class="col-md-4">
                    <div class="col3">
                        @if (isset($clusters['all'][1][0]))
                          <span class="largetext fixed"><?php echo (int)$clusters['all'][1][0]->counter; ?></span>
                        @else
                          <span class="largetext fixed">0</span>
                        @endif
                    </div>
                    <div class="col3">
                        @if (isset($clusters['all'][1][1]))
                          <span class="largetext repairable"><?php echo (int)$clusters['all'][1][1]->counter; ?></span>
                        @else
                          <span class="largetext repairable">0</span>
                        @endif
                    </div>
                    <div class="col3">
                        @if (isset($clusters['all'][1][2]))
                          <span class="largetext dead"><?php echo (int)$clusters['all'][1][2]->counter; ?></span>
                        @else
                          <span class="largetext dead">0</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">

                    <div class="category-detail">
                        <table cellspacing="0">
                            <thead>
                                <tr>
                                    <th colspan="3">
                                        Computers and Home Office
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="table-label">Most seen:</td>
                                    @if (isset($mostleast[1]['most_seen'][0]))
                                      <td class="table-data"><?php echo $mostleast[1]['most_seen'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[1]['most_seen'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td class="table-label">Most repaired:</td>
                                    @if (isset($mostleast[1]['most_repaired'][0]))
                                      <td class="table-data"><?php echo $mostleast[1]['most_repaired'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[1]['most_repaired'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td class="table-label">Least repaired:</td>
                                    @if (isset($mostleast[1]['least_repaired'][0]))
                                      <td class="table-data"><?php echo $mostleast[1]['least_repaired'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[1]['least_repaired'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                            </tbody>
                        </table>
                    </div>


                </div>
            </div>


            <div class="row">
                <div class="col-md-2  text-center">
                    <img class="cluster big img-responsive" src="/assets/icons/Category_Icons-02.png">
                    <!-- <i class="cluster big cluster-2"></i>                -->

                </div>
                <div class="col-md-4">
                    <div class="col3">
                        @if (isset($clusters['all'][2][0]))
                          <span class="largetext fixed"><?php echo (int)$clusters['all'][2][0]->counter; ?></span>
                        @else
                          <span class="largetext fixed">0</span>
                        @endif
                    </div>
                    <div class="col3">
                        @if (isset($clusters['all'][2][1]))
                          <span class="largetext repairable"><?php echo (int)$clusters['all'][2][1]->counter; ?></span>
                        @else
                          <span class="largetext repairable">0</span>
                        @endif
                    </div>
                    <div class="col3">
                        @if (isset($clusters['all'][2][2]))
                          <span class="largetext dead"><?php echo (int)$clusters['all'][2][2]->counter; ?></span>
                        @else
                          <span class="largetext dead">0</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="category-detail">
                        <table cellspacing="0">
                            <thead>
                                <tr>
                                    <th colspan="3">
                                        Electronic Gadgets
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="table-label">Most seen:</td>
                                    @if (isset($mostleast[2]['most_seen'][0]))
                                      <td class="table-data"><?php echo $mostleast[2]['most_seen'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[2]['most_seen'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td class="table-label">Most repaired:</td>
                                    @if (isset($mostleast[2]['most_repaired'][0]))
                                      <td class="table-data"><?php echo $mostleast[2]['most_repaired'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[2]['most_repaired'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td class="table-label">Least repaired:</td>
                                    @if (isset($mostleast[2]['least_repaired'][0]))
                                      <td class="table-data"><?php echo $mostleast[2]['least_repaired'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[2]['least_repaired'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                            </tbody>
                        </table>
                    </div>


                </div>
            </div>

            <div class="row">
                <div class="col-md-2  text-center">
                    <img class="cluster big img-responsive" src="/assets/icons/Category_Icons-03.png">
                    <!-- <i class="cluster big cluster-3"></i>                -->
                </div>
                <div class="col-md-4">
                    <div class="col3">
                        @if (isset($clusters['all'][3][0]))
                          <span class="largetext fixed"><?php echo (int)$clusters['all'][3][0]->counter; ?></span>
                        @else
                          <span class="largetext fixed">0</span>
                        @endif
                    </div>
                    <div class="col3">
                        @if (isset($clusters['all'][3][1]))
                          <span class="largetext repairable"><?php echo (int)$clusters['all'][3][1]->counter; ?></span>
                        @else
                          <span class="largetext repairable">0</span>
                        @endif
                    </div>
                    <div class="col3">
                        @if (isset($clusters['all'][3][2]))
                          <span class="largetext dead"><?php echo (int)$clusters['all'][3][2]->counter; ?></span>
                        @else
                          <span class="largetext dead">0</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="category-detail">
                        <table cellspacing="0">
                            <thead>
                                <tr>
                                    <th colspan="3">
                                        Home Entertainment
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="table-label">Most seen:</td>
                                    @if (isset($mostleast[3]['most_seen'][0]))
                                      <td class="table-data"><?php echo $mostleast[3]['most_seen'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[3]['most_seen'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td class="table-label">Most repaired:</td>
                                    @if (isset($mostleast[3]['most_repaired'][0]))
                                      <td class="table-data"><?php echo $mostleast[3]['most_repaired'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[3]['most_repaired'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td class="table-label">Least repaired:</td>
                                    @if (isset($mostleast[3]['least_repaired'][0]))
                                      <td class="table-data"><?php echo $mostleast[3]['least_repaired'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[3]['least_repaired'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
            <div class="row">
                <div class="col-md-2 text-center">
                    <img class="cluster big img-responsive" src="/assets/icons/Category_Icons-04.png">
                    <!-- <i class="cluster big cluster-4"></i>                -->
                </div>
                <div class="col-md-4">
                    <div class="col3">
                        @if (isset($clusters['all'][4][0]))
                          <span class="largetext fixed"><?php echo (int)$clusters['all'][4][0]->counter; ?></span>
                        @else
                          <span class="largetext fixed">0</span>
                        @endif
                    </div>
                    <div class="col3">
                        @if (isset($clusters['all'][4][1]))
                          <span class="largetext repairable"><?php echo (int)$clusters['all'][4][1]->counter; ?></span>
                        @else
                          <span class="largetext repairable">0</span>
                        @endif
                    </div>
                    <div class="col3">
                        @if (isset($clusters['all'][4][2]))
                          <span class="largetext dead"><?php echo (int)$clusters['all'][4][2]->counter; ?></span>
                        @else
                          <span class="largetext dead">0</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="category-detail">
                        <table cellspacing="0">
                            <table cellspacing="0">
                            <thead>
                                <tr>
                                    <th colspan="3">
                                        Kitchen and Household Items
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="table-label">Most seen:</td>
                                    @if (isset($mostleast[4]['most_seen'][0]))
                                      <td class="table-data"><?php echo $mostleast[4]['most_seen'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[4]['most_seen'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td class="table-label">Most repaired:</td>
                                    @if (isset($mostleast[4]['most_repaired'][0]))
                                      <td class="table-data"><?php echo $mostleast[4]['most_repaired'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[4]['most_repaired'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td class="table-label">Least repaired:</td>
                                    @if (isset($mostleast[4]['least_repaired'][0]))
                                      <td class="table-data"><?php echo $mostleast[4]['least_repaired'][0]->name; ?></td>
                                      <td class="table-count"><?php echo $mostleast[4]['least_repaired'][0]->counter; ?></td>
                                    @else
                                      <td class="table-data">N/A</td>
                                      <td class="table-count">0</td>
                                    @endif
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
                <h2><span class="title-text">Devices Restarted per Category</span></h2>
            </div>
            @php( $c = 1 )
            @foreach($clusters as $key => $cluster)
            <div class="col-md-10 <?php echo($c == 1 ? 'show' : 'hide'); ?> bargroup" id="<?php echo $key; ?>">

                <div class="row">
                    @for ($i = 1; $i <= 2; $i++)
                    <div class="col-md-2">
                        <!-- <span class="cluster big cluster-1"></span> -->
                        <img class="cluster big img-responsive" src="/assets/icons/Category_Icons-0<?php echo $i ?>.png">
                    </div>
                    <div class="col-md-4">
                        @if (array_key_exists(0, $cluster[$i]))
                          <div class="barpiece fixed" style="width :<?php echo FixometerHelper::barChartValue($cluster[1][0]->counter, $cluster[1]['total']); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo FixometerHelper::barChartValue($cluster[1][0]->counter, $cluster[1]['total']) + 15; ?>%</div>
                        @else
                          <div class="barpiece fixed" style="width :0%">&nbsp;</div><div class="barpiece-label fixed">0%</div>
                        @endif
                        @if (array_key_exists(1, $cluster[$i]))
                          <div class="barpiece repairable" style="width :<?php echo FixometerHelper::barChartValue($cluster[1][1]->counter, $cluster[1]['total']); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo FixometerHelper::barChartValue($cluster[1][1]->counter, $cluster[1]['total']) + 15; ?>%</div>
                        @else
                          <div class="barpiece repairable" style="width :0%">&nbsp;</div><div class="barpiece-label repairable">0%</div>
                        @endif
                        @if (array_key_exists(2, $cluster[$i]))
                          <div class="barpiece end-of-life" style="width :<?php echo FixometerHelper::barChartValue($cluster[1][2]->counter, $cluster[1]['total']); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo FixometerHelper::barChartValue($cluster[1][2]->counter, $cluster[1]['total']) + 15; ?>%</div>
                        @else
                          <div class="barpiece end-of-life" style="width :0%">&nbsp;</div><div class="barpiece-label dead">0%</div>
                        @endif
                    </div>
                    @endfor

                </div>

                <div class="row">
                    @for ($i = 3; $i <= 4; $i++)
                    <div class="col-md-2">
                        <!-- <span class="cluster big cluster-3"></span> -->
                        <img class="cluster big img-responsive" src="/assets/icons/Category_Icons-0<?php echo $i ?>.png">
                    </div>
                    <div class="col-md-4">
                        @if (array_key_exists(0, $cluster[$i]))
                          <div class="barpiece fixed" style="width :<?php echo FixometerHelper::barChartValue($cluster[3][0]->counter, $cluster[3]['total']); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo FixometerHelper::barChartValue($cluster[3][0]->counter, $cluster[3]['total']) + 15; ?>%</div>
                        @else
                          <div class="barpiece fixed" style="width :0%">&nbsp;</div><div class="barpiece-label fixed">0%</div>
                        @endif
                        @if (array_key_exists(0, $cluster[$i]))
                          <div class="barpiece repairable" style="width :<?php echo FixometerHelper::barChartValue($cluster[3][1]->counter, $cluster[3]['total']); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo FixometerHelper::barChartValue($cluster[3][1]->counter, $cluster[3]['total']) + 15; ?>%</div>
                        @else
                          <div class="barpiece repairable" style="width :0%">&nbsp;</div><div class="barpiece-label repairable">0%</div>
                        @endif
                        @if (array_key_exists(0, $cluster[$i]))
                          <div class="barpiece end-of-life" style="width :<?php echo FixometerHelper::barChartValue($cluster[3][2]->counter, $cluster[3]['total']); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo FixometerHelper::barChartValue($cluster[3][2]->counter, $cluster[3]['total']) + 15; ?>%</div>
                        @else
                          <div class="barpiece end-of-life" style="width :0%">&nbsp;</div><div class="barpiece-label dead">0%</div>
                        @endif
                    </div>
                    @endfor


                </div>


            </div>
              @php( $c++ )
            @endforeach

            <div class="col-md-2">
                <div class=" barpiece-tabs">
                    <ul>
                    @php( $c = 1 )
                    @foreach($clusters as $key => $cluster)
                        <li><button class="btn btn-primary btn-sm <?php echo($c == 1 ? 'active' : ''); ?> switch-view" data-family=".bargroup" data-target="#<?php echo $key; ?>"><?php echo strtoupper($key); ?></button></li>
                      @php( $c++ )
                    @endforeach


                    </ul>
                </div>

            </div>
            </section>





        </div>

    </div>

    <!-- EOF Tabs -->

    <br />
    <br />
    <br />
    <br />

</div>




<div class="modal fade" tabindex="-1" role="dialog" id="esw">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Share your group's stats</h4>
      </div>
      <div class="modal-body">
        <p>Copy and paste the code snippets below into a page on your website to share your group's achievements!</p>
        <div><strong>Headline stats</strong></div>
        <p>This widget shows the headline stats for your group &mdash; the number of participants at your parties; the hours volunteered; the number of parties thrown; the amount of waste prevented and the amount of CO<sub>2</sub> emissions prevented.
        </p>
        <code style="padding:0">
            <pre>&lt;iframe src="https://community.therestartproject.org/group/stats/<?php echo $group->idgroups; ?>" frameborder="0" width="100%" height="115"&gt;&lt;/iframe&gt;</pre>
        </code>

        <div><strong>CO<sub>2</sub> equivalence visualisation</strong></div>
        <p>This widget displays an infographic of an easy-to-understand equivalent of the CO<sub>2</sub> emissions that your group has diverted, such as equivalent number of cars manufactured.</p>
        <code style="padding:0">
            <pre>&lt;iframe src="https://community.therestartproject.org/outbound/info/group/<?php echo $group->idgroups; ?>" frameborder="0" width="700" height="850"&gt;&lt;/iframe&gt;</pre>
        </code>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
@endsection
