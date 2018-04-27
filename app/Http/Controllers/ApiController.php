<?php

namespace App\Http\Controllers;

use App\Party;
use App\Device;

class ApiController extends Controller
{
  public function homepage_data() {
      $result = array();

      $Party = new Party;
      $Device = new Device;

      $allparties = $Party->ofThisGroup('admin', true, true);

      $participants = 0;
      $hours_volunteered = 0;

      foreach($allparties as $i => $party){
          $participants += $party->pax;

          // TODO: extract hours volunteered calculation out.
          $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);
      }

      $co2Total = $Device ->getWeights();

      $result['hours_volunteered'] = $hours_volunteered;
      $result['items_fixed'] = $Device->statusCount()[0]->counter;
      $result['weights'] = $co2Total[0]->total_weights;

      echo json_encode($result);
  }
}
