@include('layouts.header_nocookie', ['iframe' => true])
@yield('content')
@if($format == 'row')

  <div id="group-main-stats">
      <div class="col">
          <h5>participants</h5>
          <span class="largetext"><?php echo $participants; ?></span>
      </div>

      <div class="col">
          <h5>hours volunteered</h5>
          <span class="largetext"><?php echo $hours_volunteered; ?></span>
      </div>

      <div class="col">
          <h5>parties thrown</h5>
          <span class="largetext"><?php echo $parties; ?></span>
      </div>

      <div class="col">
          <h5>waste prevented</h5>
          <span class="largetext">
              {{ number_format(round($waste_total), 0) }} kg
          </span>
      </div>

      <div class="col">
          <h5>CO<sub>2</sub>e emissions prevented</h5>

          <span class="largetext">{{ number_format(round($co2_total), 0) }} kg</span>
      </div>

  </div>

@elseif($format == 'double-row')

  <div id="group-main-stats">
      <div class="group-stats-row first-row">
           <div class="col">
                  <h5>waste prevented</h5>
                  <span class="largetext">
                      <?php echo $waste_total; ?> kg
                  </span>
              </div>

              <div class="col">
                  <h5>CO<sub>2</sub> emission prevented</h5>

                  <span class="largetext"><?php echo $co2_total; ?> kg</span>
              </div>
      </div>
      <div class="group-stats-row second-row">
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
                  <span class="largetext"><?php echo $parties; ?></span>
              </div>
      </div>

  </div>

@elseif($format == 'mini')

  <div id="group-main-stats" class="mini">
      <div class="col">
          <h5>parties thrown</h5>
          <span class="largetext"><?php echo $parties; ?></span>
      </div>

      <div class="col">
          <h5>waste prevented</h5>
          <span class="largetext">
              <?php echo $waste_total; ?> kg
          </span>
      </div>

      <div class="col">
          <h5>CO<sub>2</sub> emission prevented</h5>

          <span class="largetext"><?php echo $co2_total; ?> kg</span>
      </div>

  </div>

@endif
@include('layouts.footer')
