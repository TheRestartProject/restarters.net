@extends('layouts.app')

@section('content')


<section class="events events__filter group-view">
  <div class="container-fluid">

    <div class="row">
      <div class="col">
        <div class="d-flex justify-content-between align-content-center">

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">FIXOMETER</a></li>
                    <li class="breadcrumb-item">@lang('events.reporting')</li>
                    <li class="breadcrumb-item active" aria-current="page">@lang('events.events-filter')</li>
                </ol>
            </nav>

          @if(isset($PartyList))
            <div class="btn-group">

              <?php
                $exportUrl = $_GET;
                unset($exportUrl['url']);
                $exportUrl = http_build_query($exportUrl);
              ?>

              <a class="btn btn-primary" href="/export/parties/?<?php echo $exportUrl; ?>">@lang('events.download-results')</a>

            </div>
          @endif

        </div>
      </div>
    </div>

    <br>

    @if(isset($response))
    <div class="row">
        <div class="col-md-12">
            <?php printResponse($response);  ?>
        </div>
    </div>
    @endif

    <?php /*<form action="/search" class="" method="get" id="filter-search">
      @csrf
      <input type="hidden" name="fltr" value="<?php echo bin2hex(openssl_random_pseudo_bytes(8)); ?>">

      <div class="row">
        <div class="col-md-2">
          <h2>Search</h2>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <select id="search-groups" name="groups[]" class="search-groups-class selectpicker form-control" data-live-search="true" multiple title="Choose groups...">
              @foreach($groups as $group)
              <option value="<?php echo $group->id; ?>"
              <?php
              if(isset($_GET['groups']) && !empty($_GET['groups'])){
                foreach($_GET['groups'] as $g){
                  if ($g == $group->id) { echo " selected "; }
                }
              }
              ?>
              ><?php echo trim($group->name); ?></option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label for="parties" class="sr-only">Parties</label>
            <select class="selectpicker form-control" id="search-parties" name="parties[]" title="Select parties..." data-live-search="true" multiple title="Choose parties...">
              @foreach($sorted_parties as $groupname => $groupparties)
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
              @endforeach
            </select>
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
            <a href="/search" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</a>
          </div>
        </div>

        <br>

        <div class="col-md-4 offset-md-2">
          <div class="form-group">
            <label for="from-date" class="sr-only">From date</label>

            <div class="input-group date">
              <input type="text" class="form-control" id="search-from-date" name="from-date" placeholder="From date..." <?php if(isset($_GET['from-date']) && !empty($_GET['from-date'])){ echo ' value="' . $_GET['from-date'] . '"'; } ?> >
              <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="form-group">
            <label for="from-date" class="sr-only">To date</label>
            <div class="input-group date">
              <input type="text" class="form-control" id="search-to-date" name="to-date" placeholder="To date..." <?php if(isset($_GET['to-date']) && !empty($_GET['to-date'])){ echo ' value="' . $_GET['to-date'] . '"'; } ?>>
              <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
            </div>
          </div>
        </div>
      </div>

    </form>*/ ?>



    @if(isset($PartyList))

      <?php /*<section class="row profiles">
        <div class="col-md-12 text-center">
          <?php
          $exportUrl = $_GET;
          unset($exportUrl['url']);
          $exportUrl = http_build_query($exportUrl);
          ?>

          <a href="/export/parties/?<?php echo $exportUrl; ?>" class="btn btn-primary"><i class="fa fa-download"></i> Download Results (CSV) </a>
        </div>
      </section>*/ ?>

      <section class="row">

          <div class="col-lg-3">

              <form id="filter-result" action="/search" method="GET">

                @csrf

                <input type="hidden" name="fltr" value="<?php echo bin2hex(openssl_random_pseudo_bytes(8)); ?>">

                <button class="btn btn-primary btn-filter" type="submit">@lang('general.filter-results')</button>
                <div class="edit-panel edit-panel__side">

                    <button class="edit-panel__reset reset" data-form="filter-result">Reset</button>

                    <div class="form-group">
                        <label for="event_group">@lang('events.by_group'):</label>
                        <div class="form-control form-control__select">
                        <select name="groups[]" id="event_group" class="field field select2-tags" multiple>
                          @foreach($groups as $group)
                          <option value="<?php echo $group->id; ?>"
                          <?php
                          if(isset($_GET['groups']) && !empty($_GET['groups'])){
                            foreach($_GET['groups'] as $g){
                              if ($g == $group->id) { echo " selected "; }
                            }
                          }
                          ?>
                          ><?php echo trim($group->name); ?></option>
                          @endforeach
                        </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="event_event">@lang('events.by_event'):</label>
                        <div class="form-control form-control__select">
                        <select name="parties[]" id="event_event" class="field select2-tags" multiple>
                            <option value="">-- Select event --</option>
                            @foreach($sorted_parties as $groupname => $groupparties)
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
                            @endforeach
                          </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="from_date">@lang('devices.from_date'):</label>
                        <input type="date" name="from-date" id="from_date" class="field form-control" <?php if(isset($_GET['from-date']) && !empty($_GET['from-date'])){ echo ' value="' . $_GET['from-date'] . '"'; } ?>>
                    </div>

                    <div class="form-group">
                        <label for="to_date">@lang('devices.to_date'):</label>
                        <input type="date" name="to-date" id="to_date" class="field form-control" <?php if(isset($_GET['to-date']) && !empty($_GET['to-date'])){ echo ' value="' . $_GET['to-date'] . '"'; } ?>>
                    </div>

                    <div class="form-group">
                        <label for="tags">@lang('groups.group_tag2'):</label>
                        <div class="form-control form-control__select">
                            <select id="tags" class="select2-tags" multiple>
                                <option value="1">Tag example 1</option>
                                <option value="2">Tag example 2</option>
                                <option value="3">Tag example 3</option>
                                <option value="4">Tag example 4</option>
                            </select>
                        </div>
                    </div>

                  </div>
              </form>

          </div>

          <div class="col-lg-9">

              <h2 id="key-stats">Key stats</h2>
              <ul class="properties">
                  <li>
                      <div>
                      <h3>Participants</h3>
                      {{{ number_format($pax, 0, '.' , ',') }}}
                      <svg width="18" height="18" viewBox="0 0 14 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M8.147,2.06c0.624,0.413 1.062,1.113 1.141,1.925c0.255,0.125 0.537,0.197 0.837,0.197c1.093,0 1.98,-0.936 1.98,-2.091c0,-1.155 -0.887,-2.091 -1.98,-2.091c-1.083,0 -1.962,0.92 -1.978,2.06Zm-1.297,4.282c1.093,0 1.98,-0.937 1.98,-2.092c0,-1.155 -0.887,-2.091 -1.98,-2.091c-1.094,0 -1.981,0.937 -1.981,2.091c0,1.155 0.887,2.092 1.981,2.092Zm0.839,0.142l-1.68,0c-1.397,0 -2.535,1.951 -2.535,3.428l0,2.92l0.006,0.034l0.141,0.047c1.334,0.44 2.493,0.587 3.447,0.587c1.863,0 2.943,-0.561 3.01,-0.597l0.132,-0.071l0.014,0l0,-2.92c0,-1.477 -1.137,-3.428 -2.535,-3.428Zm3.276,-1.937l-1.667,0c-0.018,0.704 -0.303,1.117 -0.753,1.573c1.242,0.391 2.152,2.358 2.152,3.795l0,0.669c1.646,-0.064 2.594,-0.557 2.657,-0.59l0.132,-0.07l0.014,0l0,-2.921c0,-1.477 -1.137,-2.456 -2.535,-2.456Zm-7.59,-0.364c0.388,0 0.748,-0.12 1.053,-0.323c0.097,-0.669 0.437,-1.253 0.921,-1.651c0.002,-0.039 0.006,-0.078 0.006,-0.117c0,-1.155 -0.887,-2.091 -1.98,-2.091c-1.093,0 -1.98,0.936 -1.98,2.091c0,1.154 0.887,2.091 1.98,2.091Zm1.779,1.937c-0.449,-0.454 -0.732,-0.863 -0.753,-1.563c-0.062,-0.005 -0.123,-0.01 -0.186,-0.01l-1.68,0c-1.398,0 -2.535,0.979 -2.535,2.456l0,2.92l0.005,0.034l0.142,0.047c1.07,0.353 2.025,0.515 2.855,0.567l0,-0.656c0,-1.437 0.909,-3.404 2.152,-3.795Z" style="fill:#0394a6;fill-rule:nonzero;"/></svg>
                      </div>
                  </li>
                  <li>
                      <div>
                      <h3>Hours volunteered</h3>
                      {{{ number_format($hours, 0, '.' , ',') }}}
                      <svg width="17" height="20" viewBox="0 0 12 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M9.268,3.161c-0.332,-0.212 -0.776,-0.119 -0.992,0.207c-0.216,0.326 -0.122,0.763 0.21,0.975c1.303,0.834 2.08,2.241 2.08,3.766c0,1.523 -0.777,2.93 -2.078,3.764c-0.001,0.001 -0.001,0.001 -0.002,0.001c-0.741,0.475 -1.601,0.725 -2.486,0.725c-0.885,0 -1.745,-0.25 -2.486,-0.725c-0.001,0 -0.001,0 -0.001,0c-1.302,-0.834 -2.08,-2.241 -2.08,-3.765c0,-1.525 0.778,-2.932 2.081,-3.766c0.332,-0.212 0.426,-0.649 0.21,-0.975c-0.216,-0.326 -0.66,-0.419 -0.992,-0.207c-1.711,1.095 -2.732,2.945 -2.732,4.948c0,2.003 1.021,3.852 2.732,4.947c0,0 0.001,0.001 0.002,0.001c0.973,0.623 2.103,0.952 3.266,0.952c1.164,0 2.294,-0.33 3.268,-0.953c1.711,-1.095 2.732,-2.944 2.732,-4.947c0,-2.003 -1.021,-3.853 -2.732,-4.948" style="fill:#0394a6;fill-rule:nonzero;"/><path d="M7.59,2.133c0.107,-0.36 -0.047,-1.227 -0.503,-1.758c-0.214,0.301 -0.335,0.688 -0.44,1.022c-0.182,0.066 -0.364,-0.014 -0.581,-0.082c-0.116,-0.037 -0.505,-0.121 -0.584,-0.245c-0.074,-0.116 0.073,-0.249 0.146,-0.388c0.051,-0.094 0.094,-0.231 0.136,-0.337c0.049,-0.126 0.07,-0.247 -0.006,-0.345c-0.462,0.034 -1.144,0.404 -1.394,0.906c-0.067,0.133 -0.101,0.393 -0.089,0.519c0.011,0.104 0.097,0.313 0.161,0.424c0.249,0.426 0.588,0.781 0.766,1.206c0.22,0.525 0.172,0.969 0.182,1.52c0.041,2.214 -0.006,2.923 -0.01,5.109c0,0.189 -0.014,0.415 0.031,0.507c0.26,0.527 1.029,0.579 1.29,-0.001c0.087,-0.191 0.028,-0.571 0.017,-0.843c-0.033,-0.868 -0.056,-1.708 -0.08,-2.526c-0.033,-1.142 -0.06,-0.901 -0.117,-1.97c-0.028,-0.529 -0.023,-1.117 0.275,-1.629c0.141,-0.24 0.657,-0.78 0.8,-1.089" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg>
                      </div>
                  </li>
                  <li>
                      <div>
                      <h3>Total events</h3>
                      {{{ number_format(count($PartyList), 0, '.' , ',') }}}
                      <svg width="18" height="18" viewBox="0 0 14 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M12.462,13.5l-11.423,0c-0.282,0 -0.525,-0.106 -0.731,-0.318c-0.205,-0.212 -0.308,-0.463 -0.308,-0.753l0,-9.215c0,-0.29 0.103,-0.541 0.308,-0.753c0.206,-0.212 0.449,-0.318 0.731,-0.318l1.038,0l0,-0.804c0,-0.368 0.127,-0.683 0.381,-0.945c0.255,-0.263 0.56,-0.394 0.917,-0.394l0.519,0c0.357,0 0.663,0.131 0.917,0.394c0.254,0.262 0.382,0.577 0.382,0.945l0,0.804l3.115,0l0,-0.804c0,-0.368 0.127,-0.683 0.381,-0.945c0.254,-0.263 0.56,-0.394 0.917,-0.394l0.519,0c0.357,0 0.663,0.131 0.917,0.393c0.254,0.263 0.381,0.578 0.381,0.946l0,0.804l1.039,0c0.281,0 0.525,0.106 0.73,0.318c0.205,0.212 0.308,0.463 0.308,0.753l0,9.215c0,0.29 -0.103,0.541 -0.308,0.753c-0.206,0.212 -0.449,0.318 -0.73,0.318Zm-0.087,-3.805l-2.25,0l0,1.909l2.25,0l0,-1.909Zm-6,0l-2.25,0l0,1.909l2.25,0l0,-1.909Zm-3,0l-2.25,0l0,1.909l2.25,0l0,-1.909Zm6,0l-2.25,0l0,1.909l2.25,0l0,-1.909Zm3,-2.658l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-3,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm3,-2.658l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-3,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-5.481,-3.307l-0.519,0c-0.07,0 -0.131,0.026 -0.182,0.079c-0.052,0.053 -0.077,0.116 -0.077,0.188l0,1.661c0,0.073 0.025,0.135 0.077,0.188c0.051,0.053 0.112,0.08 0.182,0.08l0.519,0c0.071,0 0.131,-0.027 0.183,-0.08c0.051,-0.053 0.077,-0.115 0.077,-0.188l0,-1.661c0,-0.072 -0.026,-0.135 -0.077,-0.188c-0.051,-0.053 -0.112,-0.079 -0.183,-0.079Zm6.231,0l-0.519,0c-0.07,0 -0.131,0.026 -0.183,0.079c-0.051,0.053 -0.077,0.116 -0.077,0.188l0,1.661c0,0.073 0.026,0.135 0.077,0.188c0.052,0.053 0.113,0.08 0.183,0.08l0.519,0c0.071,0 0.131,-0.027 0.183,-0.08c0.051,-0.053 0.077,-0.115 0.077,-0.188l0,-1.661c0,-0.072 -0.026,-0.135 -0.077,-0.188c-0.052,-0.053 -0.112,-0.079 -0.183,-0.079Z" style="fill:#0394a6;fill-rule:nonzero;"/></svg>
                      </div>
                  </li>
                  <li>
                      <div>
                      <h3>Waste prevented</h3>
                      {{{ number_format(round(round($totalWeight)), 0, '.' , ',') }}} kg
                      <svg width="17" height="17" viewBox="0 0 13 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.15,0c0,0 -15.921,1.349 -11.313,10.348c0,0 0.59,-1.746 2.003,-3.457c0.852,-1.031 2,-2.143 3.463,-2.674c0.412,-0.149 0.696,0.435 0.094,0.727c0,0 -4.188,2.379 -4.732,6.112c0,0 1.805,1.462 3.519,1.384c1.714,-0.078 4.268,-1.078 4.707,-3.551c0.44,-2.472 1.245,-6.619 2.259,-8.889Z" style="fill:#0394a6;"/><path d="M1.147,13.369c0,0 0.157,-0.579 0.55,-2.427c0.394,-1.849 0.652,-0.132 0.652,-0.132l-0.25,2.576l-0.952,-0.017Z" style="fill:#0394a6;"/></g></svg>
                      </div>
                  </li>
                  <li>
                      <div>
                      <h3>CO<sub>2</sub> emissions prevented</h3>
                      {{{ number_format(round(round($totalCO2)), 0, '.' , ',') }}} kg
                      <svg width="20" height="12" viewBox="0 0 15 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><circle cx="2.854" cy="6.346" r="2.854" style="fill:#0394a6;"/><circle cx="11.721" cy="5.92" r="3.279" style="fill:#0394a6;"/><circle cx="7.121" cy="4.6" r="4.6" style="fill:#0394a6;"/><rect x="2.854" y="6.346" width="8.867" height="2.854" style="fill:#0394a6;"/></g></svg>
                      </div>
                  </li>
              </ul>

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
                                <time datetime="<?php echo $party->event_date; ?>"><?php echo substr($party->start, 0, -3); ?></time>

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
                          @if (isset($top[0]))
                            <div class="col-md-4"><div class="topper  text-center"><?php echo $top[0]->name . ' [' . $top[0]->counter . ']'; ?></div></div>
                          @else
                            <div class="col-md-4"><div class="topper  text-center">N/A [0]</div></div>
                          @endif
                          @if (isset($top[1]))
                            <div class="col-md-4"><div class="topper  text-center"><?php echo $top[1]->name . ' [' . $top[1]->counter . ']'; ?></div></div>
                          @else
                            <div class="col-md-4"><div class="topper  text-center">N/A [0]</div></div>
                          @endif
                          @if (isset($top[2]))
                            <div class="col-md-4"><div class="topper  text-center"><?php echo $top[2]->name . ' [' . $top[2]->counter . ']'; ?></div></div>
                          @else
                            <div class="col-md-4"><div class="topper  text-center">N/A [0]</div></div>
                          @endif
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
                          @for($i = 1; $i<= $manufacture_eql_to; $i++)
                              <div class="col-md-3 text-center">
                                  <img src="/assets/icons/<?php echo $manufacture_img; ?>" class="img-responsive">
                              </div>
                          @endfor
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
                              @if (isset($clusters['all'][1][0]))
                                <span class="largetext fixed"><?php echo $clusters['all'][1][0]->counter; ?></span>
                              @else
                                <span class="largetext fixed">0</span>
                              @endif
                          </div>
                          <div class="col3">
                              @if (isset($clusters['all'][1][1]))
                                <span class="largetext repairable"><?php echo $clusters['all'][1][1]->counter; ?></span>
                              @else
                                <span class="largetext repairable">0</span>
                              @endif
                          </div>
                          <div class="col3">
                              @if (isset($clusters['all'][1][2]))
                                <span class="largetext dead"><?php echo $clusters['all'][1][2]->counter; ?></span>
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
                          <i class="cluster big cluster-2"></i>

                      </div>
                      <div class="col-md-4">
                          <div class="col3">
                              @if (isset($clusters['all'][2][0]))
                                <span class="largetext fixed"><?php echo $clusters['all'][2][0]->counter; ?></span>
                              @else
                                <span class="largetext fixed">0</span>
                              @endif
                          </div>
                          <div class="col3">
                              @if (isset($clusters['all'][2][1]))
                                <span class="largetext repairable"><?php echo $clusters['all'][2][1]->counter; ?></span>
                              @else
                                <span class="largetext repairable">0</span>
                              @endif
                          </div>
                          <div class="col3">
                              @if (isset($clusters['all'][2][2]))
                                <span class="largetext dead"><?php echo $clusters['all'][2][2]->counter; ?></span>
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
                          <i class="cluster big cluster-3"></i>

                      </div>
                      <div class="col-md-4">
                          <div class="col3">
                            @if (isset($clusters['all'][3][0]))
                              <span class="largetext fixed"><?php echo $clusters['all'][3][0]->counter; ?></span>
                            @else
                              <span class="largetext fixed">0</span>
                            @endif
                          </div>
                          <div class="col3">
                            @if (isset($clusters['all'][3][1]))
                              <span class="largetext repairable"><?php echo $clusters['all'][3][1]->counter; ?></span>
                            @else
                              <span class="largetext repairable">0</span>
                            @endif
                          </div>
                          <div class="col3">
                            @if (isset($clusters['all'][3][2]))
                              <span class="largetext dead"><?php echo $clusters['all'][3][2]->counter; ?></span>
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
                          <i class="cluster big cluster-4"></i>

                      </div>
                      <div class="col-md-4">
                          <div class="col3">
                            @if (isset($clusters['all'][4][0]))
                              <span class="largetext fixed"><?php echo $clusters['all'][4][0]->counter; ?></span>
                            @else
                              <span class="largetext fixed">0</span>
                            @endif
                          </div>
                          <div class="col3">
                            @if (isset($clusters['all'][4][1]))
                              <span class="largetext repairable"><?php echo $clusters['all'][4][1]->counter; ?></span>
                            @else
                              <span class="largetext repairable">0</span>
                            @endif
                          </div>
                          <div class="col3">
                            @if (isset($clusters['all'][4][2]))
                              <span class="largetext dead"><?php echo $clusters['all'][4][2]->counter; ?></span>
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
                      <h3>Devices Restarted per Category</h3>
                  </div>
                  @php( $c = 1 )
                  @foreach($clusters as $key => $cluster)
                  <div class="col-md-12 <?php echo($c == 1 ? 'show' : 'hide'); ?> bargroup" id="<?php echo $key; ?>">

                      <div class="row">
                        @for ($i = 1; $i <= 2; $i++)
                          <div class="col-md-2">
                              <span class="cluster big cluster-<?php echo $i ?>"></span>
                          </div>
                          <div class="col-md-4">
                              @if (array_key_exists(0, $cluster[$i]))
                                <div class="barpiece fixed" style="width :<?php echo round((($cluster[1][0]->counter / $cluster[1]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo ($cluster[1]['total'] > 0 ? round((($cluster[1][0]->counter / $cluster[1]['total']) * 100) , 2) : '0' ); ?>%</div>
                              @else
                                <div class="barpiece fixed" style="width :0%">&nbsp;</div><div class="barpiece-label fixed">0%</div>
                              @endif

                              @if (array_key_exists(1, $cluster[$i]))
                                <div class="barpiece repairable" style="width :<?php echo round((($cluster[1][1]->counter / $cluster[1]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo ($cluster[1]['total'] > 0 ? round((($cluster[1][1]->counter / $cluster[1]['total']) * 100) , 2) : '0'  ); ?>%</div>
                              @else
                                <div class="barpiece repairable" style="width :0%">&nbsp;</div><div class="barpiece-label repairable">0%</div>
                              @endif

                              @if (array_key_exists(2, $cluster[$i]))
                                <div class="barpiece end-of-life" style="width :<?php echo round((($cluster[1][2]->counter / $cluster[1]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo ($cluster[1]['total'] > 0 ? round((($cluster[1][2]->counter / $cluster[1]['total']) * 100) , 2) : '0' ) ; ?>%</div>
                              @else
                                <div class="barpiece end-of-life" style="width :0%">&nbsp;</div><div class="barpiece-label dead">0%</div>
                              @endif
                          </div>
                          @endfor
                      </div>

                      <div class="row">
                        @for ($i = 3; $i <= 4; $i++)
                          <div class="col-md-2">
                              <span class="cluster big cluster-<?php echo $i ?>"></span>
                          </div>
                          <div class="col-md-4">
                              @if (array_key_exists(0, $cluster[$i]))
                                <div class="barpiece fixed" style="width :<?php echo round((($cluster[3][0]->counter / $cluster[3]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label fixed"><?php echo ($cluster[3]['total'] > 0 ? round((($cluster[3][0]->counter / $cluster[3]['total']) * 100) , 2) : '0' );  ?>%</div>
                              @else
                                <div class="barpiece fixed" style="width :0%">&nbsp;</div><div class="barpiece-label fixed">0%</div>
                              @endif

                              @if (array_key_exists(1, $cluster[$i]))
                                <div class="barpiece repairable" style="width :<?php echo round((($cluster[3][1]->counter / $cluster[3]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label repairable"><?php echo ($cluster[3]['total'] > 0 ? round((($cluster[3][1]->counter / $cluster[3]['total']) * 100) , 2) : '0' );  ?>%</div>
                              @else
                                <div class="barpiece repairable" style="width :0%">&nbsp;</div><div class="barpiece-label repairable">0%</div>
                              @endif

                              @if (array_key_exists(2, $cluster[$i]))
                                <div class="barpiece end-of-life" style="width :<?php echo round((($cluster[3][2]->counter / $cluster[3]['total']) * 100) , 4); ?>%">&nbsp;</div><div class="barpiece-label dead"><?php echo ($cluster[3]['total'] > 0 ? round((($cluster[3][2]->counter / $cluster[3]['total']) * 100) , 2) : '0' );  ?>%</div>
                              @else
                                <div class="barpiece end-of-life" style="width :0%">&nbsp;</div><div class="barpiece-label dead">0%</div>
                              @endif
                          </div>
                          @endfor
                      </div>


                  </div>
                  @php( $c++ )
                  @endforeach


              </section>


          </div>
      </div>
    @endif
</div>
@endsection
