<?php

namespace App\Http\Controllers;

use App\Device;
use App\Group;
use App\Party;
use Request;

class OutboundController extends Controller
{
    /** type can be either party or group
     * id is id of group or party to display.
     *
     * @OA\Get(
     *      path="/api/outbound/info/{type}/{id}/{format}",
     *      operationId="outboundInfo",
     *      tags={"Outbound"},
     *      summary="Get CO2-impact stats for a group/event, formatted for embeddable share widgets",
     *      description="Public endpoint, no authentication required. Used from external share plugins/widgets (e.g. group and event share buttons on third-party sites) to render CO2-saving comparisons (equivalent to driving/watching TV/manufacturing cars or sofas). When called under /api/*, returns JSON; when called outside the API prefix it instead renders a Blade view (outbound.info or visualisations) - only the API/JSON behaviour is relevant to this spec. Returns 404 if id is not numeric, or if the group/party cannot be found.",
     *      @OA\Parameter(
     *          name="type", in="path", required=true,
     *          description="Which kind of entity id refers to.",
     *          @OA\Schema(type="string", enum={"party","group"})
     *      ),
     *      @OA\Parameter(
     *          name="id", in="path", required=true,
     *          description="Numeric ID of the party (event) or group to report on.",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="format", in="path", required=false,
     *          description="Output shape. 'fixometer' (default) returns a car/TV comparison plus a manufacturing comparison. 'consume', 'manufacture' and 'leaf' return a single title/measure/equal_to comparison sized for a specific widget.",
     *          @OA\Schema(type="string", enum={"fixometer","consume","manufacture","leaf"}, default="fixometer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation. Shape depends on format: 'fixometer' returns {info, co2}; all other formats return {format, co2, title, measure, equal_to}.",
     *          @OA\JsonContent(
     *              @OA\Property(property="info", type="object", description="Only present when format=fixometer.", nullable=true),
     *              @OA\Property(property="format", type="string", nullable=true, description="Echoes the requested format; only present when format is not fixometer."),
     *              @OA\Property(property="co2", type="number", description="Total CO2 (kg) saved by the group/event."),
     *              @OA\Property(property="title", type="string", nullable=true),
     *              @OA\Property(property="measure", type="string", nullable=true),
     *              @OA\Property(property="equal_to", nullable=true, description="Numeric or formatted-string comparison value, depending on format.")
     *          )
     *      ),
     *      @OA\Response(response=404, description="id is not numeric, or the referenced party/group does not exist.")
     * )
     */
    public static function info($type, $id, $format = 'fixometer', $return = 'view')
    {

        // Ensure that ID is a number, otherwise show 404
        if (! is_numeric($id) && ! filter_var($id, FILTER_VALIDATE_INT)) {
            abort(404);
        } else {
            // Define variables
            $info = [];
            $co2 = 0;
            $id = (int) $id;
            $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);

            // Get the data by type
            if (strtolower($type) == 'party') {
                $event = Party::find($id);

                if (! $event) {
                    abort(404);
                }

                $eventStats = $event->getEventStats();
                $co2 = $eventStats['co2_total'];
            } elseif (strtolower($type) == 'group') {
                $group = Group::find($id);

                if (! $group) {
                    abort(404);
                }
                $groupStats = $group->getGroupStats();
                $co2 = $groupStats['co2_total'];
            }

            if ($format == 'fixometer') {
                if ($co2 > 6000) {
                    $info['consume_class'] = 'car';
                    $info['consume_image'] = 'Counters_C2_Driving.svg';
                    $info['consume_label'] = 'Equal to driving';
                    $info['consume_eql_to'] = (1 / 0.12) * $co2;
                    $info['consume_eql_to'] = number_format(round($info['consume_eql_to']), 0, '.', ',').'<small>km</small>';

                    $info['manufacture_eql_to'] = round($co2 / 6000);
                    $info['manufacture_img'] = 'Icons_04_Assembly_Line.svg';
                    $info['manufacture_label'] = 'or like the manufacture of <span class="dark">'.$info['manufacture_eql_to'].'</span> cars';
                    $info['manufacture_legend'] = ' 6000kg of CO<sub>2</sub>';
                } else {
                    $info['consume_class'] = 'tv';
                    $info['consume_image'] = 'Counters_C1_TV.svg';
                    $info['consume_label'] = 'Like watching TV for';
                    $info['consume_eql_to'] = ((1 / 0.024) * $co2) / 24;
                    $info['consume_eql_to'] = number_format(round($info['consume_eql_to']), 0, '.', ',').' <small>days</small>';

                    $info['manufacture_eql_to'] = round($co2 / 100);
                    $info['manufacture_img'] = 'Icons_03_Sofa.svg';
                    $info['manufacture_label'] = 'or like the manufacture of <span class="dark">'.$info['manufacture_eql_to'].'</span> sofas';
                    $info['manufacture_legend'] = ' 100kg of CO<sub>2</sub>';
                }

                // Return json for api.php
                if (Request::is('api*')) {
                    return response()->json([
                            'info' => $info,
                            'co2' => round($co2),
                        ]);
                } else {
                    return view('outbound.info', [
                            'info' => $info,
                            'co2' => round($co2),
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
                    $equal_to = number_format(((1 / 0.024) * $co2) / 24, 0, '.', ',');
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
                } elseif ($format == 'leaf') { // Display new sapling / hectare stats.
                    // All constructed in Vue.  We pass the raw CO2 value.
                    $title = '';
                    $measure = '';
                    $equal_to = $co2;
                } else { // Display sofa
                    $title = 'Like manufacturing';
                    $measure = 'sofa';
                    $equal_to = round($co2 / 100);
                }

                // Return json for api.php
                if (Request::is('api*')) {
                    return response()->json([
                            'format'        => $format,
                            'co2'           => round($co2),
                            'title'         => $title,
                            'measure'   => $measure,
                            'equal_to'  => $equal_to,
                        ]);
                } else {
                    return view('visualisations', [
                            'format'        => $format,
                            'co2'           => round($co2),
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
