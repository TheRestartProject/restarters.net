@extends('layouts.app')

@section('content')

<section class="events events__filter group-view">
  <div class="container">

      @if (\Session::has('success'))
          <div class="alert alert-success">
              {!! \Session::get('success') !!}
          </div>
      @endif
      @if (\Session::has('warning'))
          <div class="alert alert-warning">
              {!! \Session::get('warning') !!}
          </div>
      @endif

      <div class="row mb-30">
          <div class="col-12 col-md-12">
              <div class="d-flex align-items-center">
                  <h1 class="mb-0 mr-30">
                      @lang('events.reporting')
                  </h1>

                  @if(isset($PartyList))
                          <?php
                          $exportUrl = $_GET;
                          unset($exportUrl['url']);
                          $exportUrl = urldecode(http_build_query($exportUrl));
                          ?>

                          <a class="btn btn-primary ml-auto" href="/export/parties/?<?php echo $exportUrl; ?>">@lang('events.download-results')</a>

                  @endif

              </div>
          </div>
      </div>

    <section class="row">

        <div class="col-lg-3">

          <form id="filter-result" action="/search" method="GET">

              @csrf

              <input type="hidden" name="fltr" value="<?php echo bin2hex(openssl_random_pseudo_bytes(8)); ?>">

              <div class="edit-panel edit-panel__side">


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
                                >{{  $party->venue }} ({{ strftime('%d/%m/%Y', $party->event_timestamp) }})</option>
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

                  @if (App\Helpers\Fixometer::hasRole($user, 'Administrator'))
                  <div class="form-group">
                      <label for="tags">@lang('groups.group_tag2'):</label>
                      <div class="form-control form-control__select">
                          <select id="tags" name="group_tags[]" class="select2-tags" multiple>
                              @foreach( $group_tags as $group_tag )
                                @if( isset($_GET['group_tags']) && in_array($group_tag->id, $_GET['group_tags']) )
                                  <option value="{{{ $group_tag->id }}}" selected>{{{ $group_tag->tag_name }}}</option>
                                @else
                                  <option value="{{{ $group_tag->id }}}">{{{ $group_tag->tag_name }}}</option>
                                @endif
                              @endforeach
                          </select>
                      </div>
                  </div>
                  @endif

                  <button class="edit-panel__reset reset" data-form="filter-result">Reset</button>
                  <button class="btn btn-secondary w-100 btn-filter" type="submit">@lang('general.filter-results')</button>
                </div>
            </form>

        </div>

        <div class="col-lg-9">

            @if(isset($PartyList))

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
                      {{{ number_format(round($totalWeight), 0, '.' , ',') }}} kg
                      <svg width="17" height="17" viewBox="0 0 13 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.15,0c0,0 -15.921,1.349 -11.313,10.348c0,0 0.59,-1.746 2.003,-3.457c0.852,-1.031 2,-2.143 3.463,-2.674c0.412,-0.149 0.696,0.435 0.094,0.727c0,0 -4.188,2.379 -4.732,6.112c0,0 1.805,1.462 3.519,1.384c1.714,-0.078 4.268,-1.078 4.707,-3.551c0.44,-2.472 1.245,-6.619 2.259,-8.889Z" style="fill:#0394a6;"/><path d="M1.147,13.369c0,0 0.157,-0.579 0.55,-2.427c0.394,-1.849 0.652,-0.132 0.652,-0.132l-0.25,2.576l-0.952,-0.017Z" style="fill:#0394a6;"/></g></svg>
                      </div>
                  </li>
                  <li>
                      <div>
                      <h3>CO<sub>2</sub> emissions prevented</h3>
                      {{{ number_format(round($totalCO2), 0, '.' , ',') }}} kg
                      <svg width="20" height="12" viewBox="0 0 15 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><circle cx="2.854" cy="6.346" r="2.854" style="fill:#0394a6;"/><circle cx="11.721" cy="5.92" r="3.279" style="fill:#0394a6;"/><circle cx="7.121" cy="4.6" r="4.6" style="fill:#0394a6;"/><rect x="2.854" y="6.346" width="8.867" height="2.854" style="fill:#0394a6;"/></g></svg>
                      </div>
                  </li>
              </ul>

              <h2>Filtered event results</h2>

            <?php $currentYear = date('Y', time()); ?>

              <div id="accordion-years" class="accordion accordion__grps accordion__share">


                  <div class="card">
                    <div class="card-header" id="heading1">
                      <h5 class="mb-0">
                          <button class="btn btn-link" data-toggle="collapse" data-target="#collapse-{{{ $currentYear }}}" aria-expanded="false" aria-controls="collapse-{{{ $currentYear }}}">
                            {{{ $currentYear }}} @include('partials/caret')
                          </button>
                      </h5>
                    </div>

                    <div id="collapse-{{{ $currentYear }}}" class="collapse show" aria-labelledby="heading-{{{ $currentYear }}}" data-parent="#accordion-years">
                      <div class="events-list-wrap">
                        <div class="table-responsive">
                            <table class="table table-events table-striped" role="table">

                                @include('partials.tables.head-events', ['group_view' => true, 'hide_invite' => true, 'filter_view' => true, 'noLogo' => true])

                                <tbody>


                @foreach($PartyList as $party)

                @php( $partyYear = date('Y', $party->eventStartTimestamp) )




                    @if( $partyYear < $currentYear )

                                  </tbody>
                              </table>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="card">
                        <div class="card-header" id="heading1">
                          @php( $currentYear = $partyYear )
                          <h5 class="mb-0">
                              <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapse-{{{ $currentYear }}}" aria-expanded="false" aria-controls="collapse-{{{ $currentYear }}}">
                                {{{ $currentYear }}} @include('partials/caret')
                              </button>
                          </h5>
                        </div>

                        <div id="collapse-{{{ $currentYear }}}" class="collapse" aria-labelledby="heading-{{{ $currentYear }}}" data-parent="#accordion-years">
                          <div class="events-list-wrap">
                            <div class="table-responsive">
                                <table class="table table-events table-striped" role="table">

                                    @include('partials.tables.head-events', ['group_view' => true, 'hide_invite' => true, 'filter_view' => true, 'noLogo' => true])

                                    <tbody>

                      @endif





                                  <tr>
                                      <td class="cell-locations">
                                        <a href="/party/view/<?php echo $party->idevents; ?>">
                                            {{ $party->getEventName() }}
                                        </a>
                                      </td>
                                      <td class="cell-date"><?php print date('d/m/Y', strtotime($party->event_date)); ?> <?php print date('H:i', strtotime($party->start)) . '-' . date('H:i', strtotime($party->end)); ?></td>
                                      <td class="cell-figure">{{ $party->pax }}</td>
                                      <td class="cell-figure">{{ $party->volunteers }}</td>
                                      <td class="cell-figure">{{ round($party->ewaste) }}<small>kg<small></td>
                                      <td class="cell-figure">{{{ number_format(round($party->co2), 0, '.', '') }}}<small>kg<small></td>
                                      <td class="cell-figure">{{{ $party->fixed_devices }}}</td>
                                      <td class="cell-figure">{{{ $party->repairable_devices }}}</td>
                                      <td class="cell-figure">{{{ $party->dead_devices }}}</td>
                                  </tr>


                @endforeach

                            </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>


              <br>

              <h2 id="environmental-impact">Environmental impact</h2>

              <div class="row row-compressed-xs no-gutters">
                  <div class="col-lg-3 d-flex flex-column">
                      <ul class="properties">
                          <li class="properties__item__full properties__item__half_xs">
                              <div>
                              <h3>Waste prevented</h3>
                              {{{  number_format(round($totalWeight), 0, '.' , ',') }}} kg
                              <svg width="17" height="17" viewBox="0 0 13 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.15,0c0,0 -15.921,1.349 -11.313,10.348c0,0 0.59,-1.746 2.003,-3.457c0.852,-1.031 2,-2.143 3.463,-2.674c0.412,-0.149 0.696,0.435 0.094,0.727c0,0 -4.188,2.379 -4.732,6.112c0,0 1.805,1.462 3.519,1.384c1.714,-0.078 4.268,-1.078 4.707,-3.551c0.44,-2.472 1.245,-6.619 2.259,-8.889Z" style="fill:#0394a6;"/><path d="M1.147,13.369c0,0 0.157,-0.579 0.55,-2.427c0.394,-1.849 0.652,-0.132 0.652,-0.132l-0.25,2.576l-0.952,-0.017Z" style="fill:#0394a6;"/></g></svg>
                              </div>
                          </li>
                          <li class="properties__item__full properties__item__half_xs">
                              <div>
                                  <h3>CO<sub>2</sub> emissions prevented</h3>
                                  {{{ number_format(round($totalCO2), 0, '.' , ',') }}} kg
                                  <svg width="20" height="12" viewBox="0 0 15 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><circle cx="2.854" cy="6.346" r="2.854" style="fill:#0394a6;"/><circle cx="11.721" cy="5.92" r="3.279" style="fill:#0394a6;"/><circle cx="7.121" cy="4.6" r="4.6" style="fill:#0394a6;"/><rect x="2.854" y="6.346" width="8.867" height="2.854" style="fill:#0394a6;"/></g></svg>
                              </div>
                          </li>
                      </ul>
                  </div>

                  <?php
                  /** find size of needed SVGs **/
                  if($totalCO2 > 6000) {
                      $consume_svg = 'svg-car1';
                      $consume_label = 'Equal to driving';
                      $consume_eql_to = (1 / 0.12) * $totalCO2;
                      $consume_legend = number_format(round($consume_eql_to), 0, '.', ',') . ' km';

                      $manufacture_svg = 'svg-car2';
                      $manufacture_label = 'Like manufacturing';
                      $manufacture_eql_to = round($totalCO2 / 6000);
                      $manufacture_legend = $manufacture_eql_to . ' ' . Str::plural('car', $manufacture_eql_to);
                  }
                  else {
                      $consume_svg = 'svg-tv';
                      $consume_label = 'Like watching TV for';
                      $consume_eql_to = ((1 / 0.024) * $totalCO2 ) / 24;
                      $consume_eql_to = number_format(round($consume_eql_to), 0, '.', ',');
                      $consume_legend = $consume_eql_to . ' ' . Str::plural('day', $consume_eql_to);

                      $manufacture_svg = 'svg-sofa';
                      $manufacture_label = 'Like manufacturing';
                      $manufacture_eql_to = round($totalCO2 / 100);
                      $manufacture_legend = $manufacture_eql_to . ' ' . Str::plural('sofa', $manufacture_eql_to);
                  }
                  ?>

                  <div class="col-lg-9 d-flex flex-column">
                      <div class="row row-compressed-xs no-gutters panel">
                          <div class="col-lg-6 d-flex flex-column">
                              <div class="stat">
                                  <h3>{{{ $consume_label }}}</h3>
                                  @include('partials/'.$consume_svg)
                                  <p>{{{ $consume_legend }}}</p>
                              </div>
                          </div>
                          <div class="col-lg-6 d-flex flex-column">
                              <div class="stat">
                                  <h3>{{{ $manufacture_label }}}</h3>
                                  @include('partials/'.$manufacture_svg)
                                  <p>{{{ $manufacture_legend }}}</p>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>

              <br>

              <h2 id="device-breakdown">Device breakdown</h2>

              <div class="row row-compressed-xs no-gutters">
                  <div class="col-lg-5">
                      <ul class="properties properties__small">
                          <li>
                              <div>
                              @php( $group_device_count = 0 )

                              @if (isset($device_count_status[0]))
                                @php( $group_device_count = (int)$device_count_status[0]->counter )
                              @endif

                              @if (isset($device_count_status[1]))
                                @php( $group_device_count += (int)$device_count_status[1]->counter )
                              @endif

                              @if (isset($device_count_status[2]))
                                @php( $group_device_count += (int)$device_count_status[2]->counter )
                              @endif

                              <h3>Total devices worked on</h3>
                              {{{ $group_device_count }}}
                              <svg width="18" height="16" viewBox="0 0 15 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M13.528,13.426l-12.056,0c-0.812,0 -1.472,-0.66 -1.472,-1.472l0,-7.933c0,-0.812 0.66,-1.472 1.472,-1.472l4.686,0l-1.426,-2.035c-0.059,-0.086 -0.039,-0.203 0.047,-0.263l0.309,-0.217c0.086,-0.06 0.204,-0.039 0.263,0.047l1.729,2.468l0.925,0l1.728,-2.468c0.06,-0.086 0.178,-0.107 0.263,-0.047l0.31,0.217c0.085,0.06 0.106,0.177 0.046,0.263l-1.425,2.035l4.601,0c0.812,0 1.472,0.66 1.472,1.472l0,7.933c0,0.812 -0.66,1.472 -1.472,1.472Zm-4.012,-9.499l-7.043,0c-0.607,0 -1.099,0.492 -1.099,1.099l0,5.923c0,0.607 0.492,1.099 1.099,1.099l7.043,0c0.606,0 1.099,-0.492 1.099,-1.099l0,-5.923c0,-0.607 -0.493,-1.099 -1.099,-1.099Zm3.439,3.248c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Zm0,-2.819c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Z" style="fill:#0394a6;"/></svg>
                              </div>
                          </li>
                          <li>
                              <div>
                              <h3>Fixed devices</h3>
                              @if (isset($device_count_status[0]))
                                {{{ (int)$device_count_status[0]->counter }}}
                              @else
                                0
                              @endif
                              <svg width="18" height="15" viewBox="0 0 14 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M6.601,1.38c1.344,-1.98 4.006,-1.564 5.351,-0.41c1.345,1.154 1.869,3.862 0,5.77c-1.607,1.639 -3.362,3.461 -5.379,4.615c-2.017,-1.154 -3.897,-3.028 -5.379,-4.615c-1.822,-1.953 -1.344,-4.616 0,-5.77c1.345,-1.154 4.062,-1.57 5.407,0.41Z" style="fill:#0394a6;"/></svg>
                              </div>
                          </li>
                          <li>
                              <div>
                              <h3>Repairable devices</h3>
                              @if (isset($device_count_status[1]))
                                {{{ (int)$device_count_status[1]->counter }}}
                              @else
                                0
                              @endif
                              <svg width="18" height="18" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.33,7.915l1.213,1.212c0.609,0.61 0.609,1.599 0,2.208l-2.208,2.208c-0.609,0.609 -1.598,0.609 -2.208,0l-1.212,-1.213l4.415,-4.415Zm-9.018,-6.811c0.609,-0.609 1.598,-0.609 2.207,0l1.213,1.213l-4.415,4.415l-1.213,-1.213c-0.609,-0.609 -0.609,-1.598 0,-2.207l2.208,-2.208Z" style="fill:#0394a6;"/><path d="M11.406,1.027c-0.61,-0.609 -1.599,-0.609 -2.208,0l-8.171,8.171c-0.609,0.609 -0.609,1.598 0,2.208l2.208,2.207c0.609,0.61 1.598,0.61 2.208,0l8.17,-8.17c0.61,-0.61 0.61,-1.599 0,-2.208l-2.207,-2.208Zm-4.373,8.359c0.162,-0.163 0.425,-0.163 0.588,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.162 -0.426,0.162 -0.588,0c-0.163,-0.162 -0.163,-0.426 0,-0.588Zm1.176,-1.177c0.163,-0.162 0.426,-0.162 0.589,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.163 -0.426,0.163 -0.589,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm-2.359,-0.006c0.162,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.426,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm3.536,-1.17c0.162,-0.163 0.426,-0.163 0.588,0c0.162,0.162 0.162,0.425 0,0.588c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.588Zm-2.359,-0.007c0.162,-0.162 0.426,-0.162 0.588,0c0.162,0.163 0.162,0.426 0,0.589c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.589Zm-2.361,-0.006c0.163,-0.163 0.426,-0.163 0.589,0c0.162,0.162 0.162,0.426 0,0.588c-0.163,0.162 -0.426,0.162 -0.589,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm3.537,-1.17c0.162,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.426,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm-2.36,-0.007c0.163,-0.162 0.426,-0.162 0.588,0c0.163,0.162 0.163,0.426 0,0.588c-0.162,0.163 -0.425,0.163 -0.588,0c-0.162,-0.162 -0.162,-0.426 0,-0.588Zm1.177,-1.177c0.162,-0.162 0.426,-0.162 0.588,0c0.162,0.163 0.162,0.426 0,0.589c-0.162,0.162 -0.426,0.162 -0.588,0c-0.163,-0.163 -0.163,-0.426 0,-0.589Z" style="fill:#0394a6;"/></g></svg>
                              </div>
                          </li>
                          <li>
                              <div>
                              <h3>End-of-life devices</h3>
                              @if (isset($device_count_status[2]))
                                {{{ (int)$device_count_status[2]->counter }}}
                              @else
                                0
                              @endif
                              <svg width="20" height="20" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M2.382,10.651c-0.16,0.287 -0.287,0.719 -0.287,0.991c0,0.064 0,0.144 0.016,0.256l-1.999,-3.438c-0.064,-0.112 -0.112,-0.272 -0.112,-0.416c0,-0.145 0.048,-0.32 0.112,-0.432l0.959,-1.679l-1.071,-0.607l3.486,-0.065l1.695,3.054l-1.087,-0.623l-1.712,2.959Zm1.536,-9.691c0.303,-0.528 0.8,-0.816 1.407,-0.816c0.656,0 1.168,0.305 1.535,0.927l0.544,0.912l-1.887,3.263l-3.054,-1.775l1.455,-2.511Zm0.223,12.457c-0.911,0 -1.663,-0.752 -1.663,-1.663c0,-0.256 0.112,-0.688 0.272,-0.96l0.512,-0.911l3.79,0l0,3.534l-2.911,0l0,0Zm3.039,-12.553c-0.24,-0.415 -0.559,-0.704 -0.943,-0.864l3.933,0c0.352,0 0.624,0.144 0.784,0.417l0.976,1.662l1.055,-0.624l-1.696,3.039l-3.469,-0.049l1.071,-0.607l-1.711,-2.974Zm6.061,9.051c0.479,0 0.88,-0.128 1.215,-0.383l-1.983,3.453c-0.16,0.272 -0.447,0.432 -0.783,0.432l-1.872,0l0,1.231l-1.791,-2.99l1.791,-2.991l0,1.248l3.423,0l0,0Zm1.534,-2.879c0.145,0.256 0.225,0.528 0.225,0.816c0,0.576 -0.368,1.183 -0.879,1.471c-0.241,0.128 -0.577,0.209 -0.912,0.209l-1.056,0l-1.886,-3.263l3.054,-1.743l1.454,2.51Z" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg>
                              </div>
                          </li>
                          <li class="properties__item__full">
                              <div>
                              <h3>Most repaired devices</h3>
                              <div class="row row-compressed properties__repair-count">

                                  @for ($i=0; $i < 3; $i++)
                                    @if (isset($top[$i]))
                                      <div class="col-6"><strong>{{{ $top[$i]->name }}}: </strong></div>
                                      <div class="col-6">{{{ $top[$i]->counter }}}</div>
                                    @else
                                      <div class="col-12"><strong>{{{ $i+1 }}}.</strong> N/A</div>
                                    @endif
                                  @endfor

                              </div>
                              <svg width="18" height="16" viewBox="0 0 15 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M13.528,13.426l-12.056,0c-0.812,0 -1.472,-0.66 -1.472,-1.472l0,-7.933c0,-0.812 0.66,-1.472 1.472,-1.472l4.686,0l-1.426,-2.035c-0.059,-0.086 -0.039,-0.203 0.047,-0.263l0.309,-0.217c0.086,-0.06 0.204,-0.039 0.263,0.047l1.729,2.468l0.925,0l1.728,-2.468c0.06,-0.086 0.178,-0.107 0.263,-0.047l0.31,0.217c0.085,0.06 0.106,0.177 0.046,0.263l-1.425,2.035l4.601,0c0.812,0 1.472,0.66 1.472,1.472l0,7.933c0,0.812 -0.66,1.472 -1.472,1.472Zm-4.012,-9.499l-7.043,0c-0.607,0 -1.099,0.492 -1.099,1.099l0,5.923c0,0.607 0.492,1.099 1.099,1.099l7.043,0c0.606,0 1.099,-0.492 1.099,-1.099l0,-5.923c0,-0.607 -0.493,-1.099 -1.099,-1.099Zm3.439,3.248c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Zm0,-2.819c0.448,0 0.812,0.364 0.812,0.812c0,0.449 -0.364,0.813 -0.812,0.813c-0.448,0 -0.812,-0.364 -0.812,-0.813c0,-0.448 0.364,-0.812 0.812,-0.812Z" style="fill:#0394a6;"/></svg>
                              </div>
                          </li>
                      </ul>
                  </div>
                  <div class="col-lg-7">
                      @include('partials.group-device-breakdown')
                  </div>
              </div>

          </div>

        @else

            @if(isset($response))
            <div class="row">
                <div class="col-md-12">
                    <?php App\Helpers\Fixometer::printResponse($response);  ?>
                </div>
            </div>
            @endif

        @endif

    </section>

  </div>

</section>

@endsection
