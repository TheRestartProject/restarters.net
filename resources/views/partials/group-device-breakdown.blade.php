<div id="accordion-grps" class="accordion accordion__grps accordion__share">

    <?php
      $category_clusters = [
        1 => 'Computers and Home Office',
        2 => 'Electronic Gadgets',
        3 => 'Home Entertainment',
        4 => 'Kitchen and Household Items'
      ];
    ?>

    @foreach( $category_clusters as $key => $category_cluster )

      <div class="card">
        <div class="card-header" id="heading{{{ $key }}}">
          <h5 class="mb-0">
              <button class="btn btn-link @if( $key > 1 ) collapsed @endif" data-toggle="collapse" data-target="#collapse{{{ $key }}}" aria-expanded="true" aria-controls="collapse{{{ $key }}}">
              {{{ $category_cluster }}} @include('partials/caret')
              </button>
          </h5>
        </div>

        <div id="collapse{{{ $key }}}" class="collapse  @if( $key == 1 ) show @endif" aria-labelledby="heading{{{ $key }}}" data-parent="#accordion-grps">
          <div class="card-body">

            <?php

              //Counters
              if ( isset($clusters['all'][$key][0]) ):
                $fixed = (int)$clusters['all'][$key][0]->counter;
              else:
                $fixed = 0;
              endif;

              if ( isset($clusters['all'][$key][1]) ):
                $repairable = (int)$clusters['all'][$key][1]->counter;
              else:
                $repairable = 0;
              endif;

              if ( isset($clusters['all'][$key][2]) ):
                $dead = (int)$clusters['all'][$key][2]->counter;
              else:
                $dead = 0;
              endif;

              //Percentages
              if (array_key_exists(0, $clusters['all'][$key])):
                $fixed_percent = App\Helpers\Fixometer::barChartValue($clusters['all'][$key][0]->counter, $clusters['all'][$key]['total']) + 15;
              else:
                $fixed_percent = null;
              endif;

              if (array_key_exists(1, $clusters['all'][$key])):
                $repairable_percent = App\Helpers\Fixometer::barChartValue($clusters['all'][$key][1]->counter, $clusters['all'][$key]['total']) + 15;
              else:
                $repairable_percent = null;
              endif;

              if (array_key_exists(2, $clusters['all'][$key])):
                $dead_percent = App\Helpers\Fixometer::barChartValue($clusters['all'][$key][2]->counter, $clusters['all'][$key]['total']) + 15;
              else:
                $dead_percent = null;
              endif;

              //Seen and repaired stats
              if ( isset( $mostleast[$key]['most_seen'][0] ) ):
                  $most_seen = $mostleast[$key]['most_seen'][0]->name;
                  $most_seen_type = $mostleast[$key]['most_seen'][0]->counter;
              else:
                  $most_seen = null;
                  $most_seen_type = null;
              endif;

              if ( isset( $mostleast[$key]['most_repaired'][0] ) ):
                  $most_repaired = $mostleast[$key]['most_repaired'][0]->name;
                  $most_repaired_type = $mostleast[$key]['most_repaired'][0]->counter;
              else:
                  $most_repaired = null;
                  $most_repaired_type = null;
              endif;

              if ( isset( $mostleast[$key]['least_repaired'][0] ) ):
                  $least_repaired = $mostleast[$key]['least_repaired'][0]->name;
                  $least_repaired_type = $mostleast[$key]['least_repaired'][0]->counter;
              else:
                  $least_repaired = null;
                  $least_repaired_type = null;
              endif;

            ?>

            @include('partials/device-mini-stats',[
              'title'               => $key,
              'fixed'               => $fixed,
              'fixed_percent'       => $fixed_percent,
              'repairable'          => $repairable,
              'repairable_percent'  => $repairable_percent,
              'dead'                => $dead,
              'dead_percent'        => $dead_percent,

              'most_seen'           => $most_seen,
              'most_seen_type'      => $most_seen_type,
              'most_repaired'       => $most_repaired,
              'most_repaired_type'  => $most_repaired_type,
              'least_repaired'      => $least_repaired,
              'least_repaired_type' => $least_repaired_type,
            ])

          </div>
        </div>
      </div>

    @endforeach

</div><!-- / accordion-grps -->
