<div class="container search-dashboard" id="admin-dashboard">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-6">
            <h1>Parties Filter
                <small>
            <?php $home_url = (hasRole($user, 'Administrator') ? '/admin' : '/host'); ?>
            <a href="<?php echo $home_url; ?>" class="btn btn-primary btn-sm"><i class="fa fa-home"></i> back to dashboard</a>
    </small>
            </h1>
        </div>
    </div>
    <?php if(isset($response)) { ?>
    <div class="row">
        <div class="col-md-12">
            <?php printResponse($response);  ?>
        </div>
    </div>
    <?php } ?>

    <section class="row profile">

        <form action="/search" class="" method="get" id="filter-search">
          <input type="hidden" name="fltr" value="<?php echo bin2hex(openssl_random_pseudo_bytes(8)); ?>">

          <div class="col-md-2">
            <h2>Search</h2>
          </div>

          <div class="col-md-5">
            <div class="form-group">
              <select id="search-groups" name="groups[]" class="search-groups-class selectpicker form-control" data-live-search="true" multiple title="Choose groups...">
                <?php foreach($groups as $group){ ?>
                <option value="<?php echo $group->id; ?>"
                <?php
                if(isset($_GET['groups']) && !empty($_GET['groups'])){
                  foreach($_GET['groups'] as $g){
                    if ($g == $group->id) { echo " selected "; }
                  }
                }
                ?>
                ><?php echo trim($group->name); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group">
              <label for="parties" class="sr-only">Parties</label>
              <select class="selectpicker form-control" id="search-parties" name="parties[]" title="Select parties..." data-live-search="true" multiple title="Choose parties...">
                <?php foreach($sorted_parties as $groupname => $groupparties){ ?>
                <optgroup label="<?php echo trim($groupname); ?>">
                  <?php foreach($groupparties as $party) { ?>
                  <option value="<?php echo $party->id; ?>" data-subtext="<?php echo strftime('%d/%m/%Y', $party->event_timestamp); ?>"
                    <?php
                    if(isset($_GET['parties']) && !empty($_GET['parties'])){
                      foreach($_GET['parties'] as $p){
                        if ($p == $party->id) { echo " selected "; }
                      }
                    }
                    ?>
                  ><?php echo $party->venue; ?></option>
                  <?php } ?>
                </optgroup>
                <?php } ?>
              </select>
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-group">
              <label for="from-date" class="sr-only">From date</label>

              <div class="input-group date">
                <input type="text" class="form-control" id="search-from-date" name="from-date" placeholder="From date..." <?php if(isset($_GET['from-date']) && !empty($_GET['from-date'])){ echo ' value="' . $_GET['from-date'] . '"'; } ?> >
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
              </div>
            </div>

            <div class="form-group">
              <label for="from-date" class="sr-only">To date</label>
              <div class="input-group date">
                <input type="text" class="form-control" id="search-to-date" name="to-date" placeholder="To date..." <?php if(isset($_GET['to-date']) && !empty($_GET['to-date'])){ echo ' value="' . $_GET['to-date'] . '"'; } ?>>
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
          </div>

          <div class="col-md-2">
            <div class="form-group">
              <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
              <a href="/search" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</a>
            </div>
          </div>
        </form>

    </section>


    <?php if($PartyList){ ?>
      <section class="row profiles">
        <div class="col-md-12 text-center">
          <?php
          $exportUrl = $_GET;
          unset($exportUrl['url']);
          $exportUrl = http_build_query($exportUrl);
          ?>

          <a href="/export/parties/?<?php echo $exportUrl; ?>" class="btn btn-primary"><i class="fa fa-download"></i> Download Results (CSV) </a>
        </div>
      </section>
      <section class="row profiles">
          <div class="col-md-12">
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
                      <span class="largetext"><?php echo count($PartyList); ?></span>
                  </div>

                  <div class="col">
                      <h5>waste prevented</h5>
                      <span class="largetext">
                          <?php echo number_format(round($totalWeight), 0, '.', ','); ?> kg
                      </span>
                  </div>

                  <div class="col">
                      <h5>CO<sub>2</sub> emission prevented</h5>

                      <span class="largetext">
                          <?php echo number_format(round($totalCO2), 0, '.', ','); ?> kg
                      </span>
                  </div>

              </div>
          </div>
      </section>

      <!-- Nav tabs -->
      <ul class="nav nav-pills nav-justified" role="tablist">
          <li role="presentation" class="active"><a href="#parties-tab" aria-controls="Parties" role="tab" data-toggle="pill">Parties</a></li>
          <li role="presentation"><a href="#impact-tab" aria-controls="Impact" role="tab" data-toggle="pill">Impact</a></li>
          <li role="presentation"><a href="#details-tab" aria-controls="Details" role="tab" data-toggle="pill">Details</a></li>
      </ul>

      <div class="tab-content">
          <div role="tabpanel" class="tab-pane active" id="parties-tab">
            <section class="row parties">
                <header>
                    <div class="col-md-12"  id="allparties">
                        <h2>
                            Filtered Party Results
                        </h2>
                    </div>
                </header>
                <br />
                <div class="col-md-12" id="party-list-header">
                    <div class="header-col header-col-2">&nbsp;</div>

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
                        <span class="icon-label">CO<sub>2</sub> Emissions prevented</span>
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
                <div class="col-md-12 fader" id="party-list">

                    <?php
                    $nodata = 0;
                    $currentYear = date('Y', time());
                    foreach($PartyList as $party){
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
                                <span class="location"><?php echo $party->venue; ?></span>
                                <time datetime="<?php echo dbDate($party->event_date); ?>"><?php echo substr($party->start, 0, -3); ?></time>

                            </div>
                        </div>
                        <div class="header-col header-col-3">
                            <button class="btn btn-primary btn-lg add-info-btn">
                                <i class="fa fa-cloud-upload"></i> Add Information
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
                                <span class="location"><?php echo $party->venue; ?></span>
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
                                 <?php echo number_format(round($party->co2), 0, '.', ''); ?> kg
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

                      <p class="big">
                          <span class="big blue"><?php echo $pax; ?> participants</span> aided by <span class="big blue"><?php echo $hours; ?> hours of volunteered time</span> worked on <span class="big blue"><?php echo ($device_count_status[0]->counter + $device_count_status[1]->counter + $device_count_status[2]->counter) ?> devices.</span>
                      </p>

                  </div>
              </section>

              <section class="row" id="impact-devices">
                  <div class="col-md-6 col-md-offset-3  text-center">

                      <div class="impact-devices-1">
                          <img src="/assets/icons/impact_device_1.jpg" class="" width="200">
                          <span class="title"><?php echo (int)$device_count_status[0]->counter;?></span>
                          <span class="legend">were fixed</span>
                      </div>

                      <div class="impact-devices-2">
                          <img src="/assets/icons/impact_device_2.jpg" class="" width="200">
                          <span class="title"><?php echo (int)$device_count_status[1]->counter;?></span>
                          <span class="legend">were still repairable</span>
                      </div>

                      <div class="impact-devices-3">
                          <img src="/assets/icons/impact_device_3.jpg" class="" width="200">
                          <span class="title"><?php echo (int)$device_count_status[2]->counter;?></span>
                          <span class="legend">were dead</span>
                      </div>

                  </div>

                  <div class="col-md-12">
                      <h2><span class="title-text">Most Repaired Devices</span></h2>

                      <div class="row">
                          <div class="col-md-4"><div class="topper  text-center"><?php echo $top[0]->name . ' [' . $top[0]->counter . ']'; ?></div></div>
                          <div class="col-md-4"><div class="topper  text-center"><?php echo $top[1]->name . ' [' . $top[1]->counter . ']'; ?></div></div>
                          <div class="col-md-4"><div class="topper  text-center"><?php echo $top[2]->name . ' [' . $top[2]->counter . ']'; ?></div></div>
                      </div>
                  </div>

              </section>

              <section class="row" id="impact-dataviz">
                  <div class="col-md-12 text-center texter">
                      <span class="datalabel">Total waste prevented: </span><span class="blue">  <?php echo number_format(round($totalWeight), 0, '.', ','); ?> kg </span>
                  </div>
                  <div class="col-md-12 text-center texter">
                      <span class="datalabel">Total CO<sub>2</sub> emission prevented: </span><span class="blue"><?php echo number_format(round($totalCO2), 0, '.', ','); ?> kg</span>
                  </div>
                  <div class="col-md-12">
                      <?php
                      /** find size of needed SVGs **/
                      if($totalCO2 > 6000) {
                          $consume_class = 'car';
                          $consume_image = 'Counters_C2_Driving.svg';
                          $consume_label = 'Equal to driving';
                          $consume_eql_to = (1 / 0.12) * $totalCO2;
                          $consume_eql_to = number_format(round($consume_eql_to), 0, '.', ',') . '<small>km</small>';

                          $manufacture_eql_to = round($totalCO2 / 6000);
                          $manufacture_img = 'Icons_04_Assembly_Line.svg';
                          $manufacture_label = 'or like the manufacture of <span class="dark">' . $manufacture_eql_to . '</span> cars';
                          $manufacture_legend = ' 6000kg of CO<sub>2</sub>';
                      }
                      else {
                          $consume_class = 'tv';
                          $consume_image = 'Counters_C1_TV.svg';
                          $consume_label = 'Like watching TV for';
                          $consume_eql_to = ((1 / 0.024) * $totalCO2) / 24;
                          $consume_eql_to = number_format(round($consume_eql_to), 0, '.', ',') . '<small>days</small>';

                          $manufacture_eql_to = round($totalCO2 / 100);
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
                          <?php for($i = 1; $i<= $manufacture_eql_to; $i++){ ?>
                              <div class="col-md-3 text-center">
                                  <img src="/assets/icons/<?php echo $manufacture_img; ?>" class="img-responsive">
                              </div>
                          <?php } ?>
                          <div class="col-md-12 text-center">
                              <div class="legend">1 <img src="/assets/icons/<?php echo $manufacture_img; ?>"> = <?php echo $manufacture_legend; ?> (approximately)</div>

                          </div>
                      </div>


                  </div>

              </section>


          </div>

          <div role="tabpanel" class="tab-pane" id="details-tab">





              <section class="row">


                <!-- Device count -->

                  <div class="col-md-12">
                      <h3>Devices Restarted</h3>
                      <div class="row">
                          <div class="col-md-4 count">
                              <div class="col">
                                  <img src="/assets/icons/fixed_circle.jpg">
                              </div>
                              <div class="col">
                                  <span class="status_title">Fixed</span>
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
                                  <span class="status_title">Repairable</span>
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
                                  <span class="status_title">Dead</span>
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
                      <h3>Category Details</h3>
                  </div>

                  <div class="row">
                      <div class="col-md-2">&nbsp;</div>
                      <div class="col-md-4">
                          <div class="col3">
                              <img src="/assets/icons/icon_fixed.png" title="fixed items" alt="Fixed Items icon">
                              <span class="subtext">fixed</span>
                          </div>
                          <div class="col3 no-brd">
                              <img src="/assets/icons/icon_repairable.png" title="repairable items" alt="repairable Items icon">
                              <span class="subtext">repairable</span>
                          </div>
                          <div class="col3">
                              <img src="/assets/icons/icon_dead.png" title="dead items" alt="dead Items icon">
                              <span class="subtext">dead</span>
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
                                              Computers and Home Office
                                          </th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <tr>
                                          <td class="table-label">Most seen:</td>
                                          <td class="table-data"><?php echo $mostleast[1]['most_seen'][0]->name; ?></td>
                                          <td class="table-count"><?php echo $mostleast[1]['most_seen'][0]->counter; ?></td>
                                      </tr>
                                      <tr>
                                          <td class="table-label">Most repaired:</td>
                                          <td class="table-data"><?php echo $mostleast[1]['most_repaired'][0]->name; ?></td>
                                          <td class="table-count"><?php echo $mostleast[1]['most_repaired'][0]->counter; ?></td>
                                      </tr>
                                      <tr>
                                          <td class="table-label">Least repaired:</td>
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
                                              Electronic Gadgets
                                          </th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <tr>
                                          <td class="table-label">Most seen:</td>
                                          <td class="table-data"><?php echo $mostleast[2]['most_seen'][0]->name; ?></td>
                                          <td class="table-count"><?php echo $mostleast[2]['most_seen'][0]->counter; ?></td>
                                      </tr>
                                      <tr>
                                          <td class="table-label">Most repaired:</td>
                                          <td class="table-data"><?php echo $mostleast[2]['most_repaired'][0]->name; ?></td>
                                          <td class="table-count"><?php echo $mostleast[2]['most_repaired'][0]->counter; ?></td>
                                      </tr>
                                      <tr>
                                          <td class="table-label">Least repaired:</td>
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
                                              Home Entertainment
                                          </th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <tr>
                                          <td class="table-label">Most seen:</td>
                                          <td class="table-data"><?php echo $mostleast[3]['most_seen'][0]->name; ?></td>
                                          <td class="table-count"><?php echo $mostleast[3]['most_seen'][0]->counter; ?></td>
                                      </tr>
                                      <tr>
                                          <td class="table-label">Most repaired:</td>
                                          <td class="table-data"><?php echo $mostleast[3]['most_repaired'][0]->name; ?></td>
                                          <td class="table-count"><?php echo $mostleast[3]['most_repaired'][0]->counter; ?></td>
                                      </tr>
                                      <tr>
                                          <td class="table-label">Least repaired:</td>
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
                                              Kitchen and Household Items
                                          </th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <tr>
                                          <td class="table-label">Most seen:</td>
                                          <td class="table-data"><?php echo $mostleast[4]['most_seen'][0]->name; ?></td>
                                          <td class="table-count"><?php echo $mostleast[4]['most_seen'][0]->counter; ?></td>
                                      </tr>
                                      <tr>
                                          <td class="table-label">Most repaired:</td>
                                          <td class="table-data"><?php echo $mostleast[4]['most_repaired'][0]->name; ?></td>
                                          <td class="table-count"><?php echo $mostleast[4]['most_repaired'][0]->counter; ?></td>
                                      </tr>
                                      <tr>
                                          <td class="table-label">Least repaired:</td>
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
                      <h3>Devices Restarted per Category</h3>
                  </div>
                  <?php
                  //dbga($clusters);
                  $c = 1;
                  foreach($clusters as $key => $cluster){ ?>
                  <div class="col-md-12 <?php echo($c == 1 ? 'show' : 'hide'); ?> bargroup" id="<?php echo $key; ?>">

                      <div class="row">
                          <div class="col-md-2">
                              <span class="cluster big cluster-1"></span>
                          </div>
                          <div class="col-md-4">
                              <div class="barpiece fixed" style="width :<?php echo round((($cluster[1][0]->counter / $cluster[1]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo ($cluster[1]['total'] > 0 ? round((($cluster[1][0]->counter / $cluster[1]['total']) * 100) , 2) : '0' ); ?>%</div>
                              <div class="barpiece repairable" style="width :<?php echo round((($cluster[1][1]->counter / $cluster[1]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo ($cluster[1]['total'] > 0 ? round((($cluster[1][1]->counter / $cluster[1]['total']) * 100) , 2) : '0'  ); ?>%</div>
                              <div class="barpiece end-of-life" style="width :<?php echo round((($cluster[1][2]->counter / $cluster[1]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo ($cluster[1]['total'] > 0 ? round((($cluster[1][2]->counter / $cluster[1]['total']) * 100) , 2) : '0' ) ; ?>%</div>
                          </div>


                          <div class="col-md-2">
                              <span class="cluster big cluster-2"></span>
                          </div>
                          <div class="col-md-4">
                              <div class="barpiece fixed" style="width :<?php echo round((($cluster[2][0]->counter / $cluster[2]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo ($cluster[2]['total'] > 0 ? round((($cluster[2][0]->counter / $cluster[2]['total']) * 100) , 2) : '0' );  ?>%</div>
                              <div class="barpiece repairable" style="width :<?php echo round((($cluster[2][1]->counter / $cluster[2]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo ($cluster[2]['total'] > 0 ? round((($cluster[2][1]->counter / $cluster[2]['total']) * 100) , 2) : '0' );  ?>%</div>
                              <div class="barpiece end-of-life" style="width :<?php echo round((($cluster[2][2]->counter / $cluster[2]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo ($cluster[2]['total'] > 0 ? round((($cluster[2][2]->counter / $cluster[2]['total']) * 100) , 2) : '0' );  ?>%</div>
                          </div>

                      </div>

                      <div class="row">
                          <div class="col-md-2">
                              <span class="cluster big cluster-3"></span>
                          </div>
                          <div class="col-md-4">
                              <div class="barpiece fixed" style="width :<?php echo round((($cluster[3][0]->counter / $cluster[3]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo ($cluster[3]['total'] > 0 ? round((($cluster[3][0]->counter / $cluster[3]['total']) * 100) , 2) : '0' );  ?>%</div>
                              <div class="barpiece repairable" style="width :<?php echo round((($cluster[3][1]->counter / $cluster[3]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo ($cluster[3]['total'] > 0 ? round((($cluster[3][1]->counter / $cluster[3]['total']) * 100) , 2) : '0' );  ?>%</div>
                              <div class="barpiece end-of-life" style="width :<?php echo round((($cluster[3][2]->counter / $cluster[3]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo ($cluster[3]['total'] > 0 ? round((($cluster[3][2]->counter / $cluster[3]['total']) * 100) , 2) : '0' );  ?>%</div>
                          </div>

                          <div class="col-md-2">
                              <span class="cluster big cluster-4"></span>
                          </div>
                          <div class="col-md-4">
                              <div class="barpiece fixed" style="width :<?php echo round((($cluster[4][0]->counter / $cluster[4]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo ($cluster[4]['total'] > 0 ? round((($cluster[4][0]->counter / $cluster[4]['total']) * 100) , 2) : '0' );  ?>%</div>
                              <div class="barpiece repairable" style="width :<?php echo round((($cluster[4][1]->counter / $cluster[4]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo ($cluster[4]['total'] > 0 ? round((($cluster[4][1]->counter / $cluster[4]['total']) * 100) , 2) : '0' );  ?>%</div>
                              <div class="barpiece end-of-life" style="width :<?php echo round((($cluster[4][2]->counter / $cluster[4]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo ($cluster[4]['total'] > 0 ? round((($cluster[4][2]->counter / $cluster[4]['total']) * 100) , 2) : '0' ); ?>%</div>
                          </div>


                      </div>


                  </div>
                  <?php
                      $c++;
                  }
                  ?>


              </section>


          </div>
      </div>
    <?php } ?>
</div>
