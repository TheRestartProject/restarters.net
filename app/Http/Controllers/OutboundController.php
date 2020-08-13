<?php

namespace App\Http\Controllers;

use App\Group;
use App\Party;
use App\Device;

use App\Helpers\FootprintRatioCalculator;
use Request;

class OutboundController extends Controller
{
  // public function __construct($model, $controller, $action){
  //
    //   parent::__construct($model, $controller, $action);
  //
    //   $this->groups = new Group;
    //  $this->parties = new Party;
    //  $this->devices = new Device;
  //
    //  $weights = $this->devices->getWeights();
  //
    //  $this->TotalWeight = $weights[0]->total_weights;
    //  $this->TotalEmission = $weights[0]->total_footprints;
    //  $this->EmissionRatio = $this->TotalEmission / $this->TotalWeight;
  // }

    /** type can be either party or group
     * id is id of group or party to display
     * */
    public static function info($type, $id, $format = 'fixometer', $return = 'view')
    {

        // Ensure that ID is a number, otherwise show 404
        if (!is_numeric($id) && !filter_var($id, FILTER_VALIDATE_INT)) {
            abort(404);
        } else {
            // Define variables
            $info = array();
            $co2 = 0;
            $id = (int)$id;
            $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);

            // Calculators
            $footprintRatioCalculator = new FootprintRatioCalculator();
            $EmissionRatio = $footprintRatioCalculator->calculateRatio();

            // Get the data by type
            if (strtolower($type) == 'party') {
                $event = Party::find($id);
                $eventStats = $event->getEventStats($EmissionRatio);
                $co2 = $eventStats['co2'];
            } elseif (strtolower($type) == 'group') {
                $group = Group::find($id);
                $groupStats = $group->getGroupStats($EmissionRatio);
                $co2 = $groupStats['co2'];
            } elseif (strtolower($type) == 'group-tag') {
                $groups = Group::join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
                  ->where('grouptags_groups.group_tag', $id)
                    ->select('groups.*')
                      ->get();
                $co2 = 0;
                foreach ($groups as $group) {
                    $groupStats = $group->getGroupStats($EmissionRatio);
                    $co2 += $groupStats['co2'];
                }
            }

            if ($format == 'fixometer') {
                if ($co2 > 6000) {
                    $info['consume_class']  = 'car';
                    $info['consume_image']  = 'Counters_C2_Driving.svg';
                    $info['consume_label']  = 'Equal to driving';
                    $info['consume_eql_to'] = (1 / 0.12) * $co2;
                    $info['consume_eql_to'] = number_format(round($info['consume_eql_to']), 0, '.', ',') . '<small>km</small>';

                    $info['manufacture_eql_to'] = round($co2 / 6000);
                    $info['manufacture_img']    = 'Icons_04_Assembly_Line.svg';
                    $info['manufacture_label']  = 'or like the manufacture of <span class="dark">' . $info['manufacture_eql_to'] . '</span> cars';
                    $info['manufacture_legend'] = ' 6000kg of CO<sub>2</sub>';
                } else {
                    $info['consume_class']  = 'tv';
                    $info['consume_image']  = 'Counters_C1_TV.svg';
                    $info['consume_label']  = 'Like watching TV for';
                    $info['consume_eql_to'] = ((1 / 0.024) * $co2 ) / 24;
                    $info['consume_eql_to'] = number_format(round($info['consume_eql_to']), 0, '.', ',') . ' <small>days</small>';

                    $info['manufacture_eql_to'] = round($co2 / 100);
                    $info['manufacture_img'] = 'Icons_03_Sofa.svg';
                    $info['manufacture_label'] = 'or like the manufacture of <span class="dark">' . $info['manufacture_eql_to'] . '</span> sofas';
                    $info['manufacture_legend'] = ' 100kg of CO<sub>2</sub>';
                }

                // Return json for api.php
                if (Request::is('api*')) {
                        return response()->json([
                            'info' => $info,
                            'co2' => $co2,
                        ]);
                } else {
                        return view('outbound.info', [
                            'info' => $info,
                            'co2' => $co2,
                        ]);
                }
            } else {
                // Consume: driving vs. watching TV
                if ($format == 'consume' && $co2 >= 3000) { // Driving graphic
                    $title = 'Equal to driving';
                    $measure = 'km';
                    $equal_to = number_format(round((1 / 0.12) * $co2), 0, '.', ',');
                } elseif ($format == 'consume' && $co2 < 3000) { // Watching TV
                    $title = 'Watching TV for';
                    $measure = 'day';
                    $equal_to = number_format(((1 / 0.024) * $co2 ) / 24, 0, '.', ',');
                } elseif ($format == 'manufacture' && $co2 > 6000) { // Display whole cars
                    $title = 'Like manufacturing';
                    $measure = 'car';
                    $equal_to = round($co2 / 6000);
                } elseif ($format == 'manufacture' && $co2 > 3000 && $co2 <= 6000) { // Display whole cars
                    $title = 'Like manufacturing';
                    $measure = 'car';
                    $equal_to = round($co2 / 6000);
                } elseif ($format == 'manufacture' && $co2 > 900 && $co2 <= 3000) { // Display half cars
                    $title = 'Like manufacturing';
                    $measure = 'half car';
                    $equal_to = round($co2 / 3000);
                } else { // Display sofa
                    $title = 'Like manufacturing';
                    $measure = 'sofa';
                    $equal_to = round($co2 / 100);
                }

                // Return json for api.php
                if (Request::is('api*')) {
                        return response()->json([
                            'format'        => $format,
                            'co2'           => $co2,
                            'title'         => $title,
                            'measure'   => $measure,
                            'equal_to'  => $equal_to,
                        ]);
                } else {
                        return view('visualisations', [
                            'format'        => $format,
                            'co2'           => $co2,
                            'title'         => $title,
                            'measure'   => $measure,
                            'equal_to'  => $equal_to,
                        ]);
                }
            }
        }

        abort(404);
    }
}
