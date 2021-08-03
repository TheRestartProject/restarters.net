<?php

namespace App\Helpers;

use DB;


class LcaStatsHelper
{

    public static function getEmissionRatioUnpowered()
    {
        return 73.35740514;
    }

    public static function getDisplacementFactor()
    {
        return 0.5;
    }

    /**
     * Calculates the average footprint per kilo of fixed devices.
     *
     * Calculated by looking at all of the fixed, non-misc devices in the database,
     * and dividing the total footprint of these devices by the total weight of these
     * devices.
     *
     * Categories table must hold 0 value for weight and footprint for Misc categories.
     *
     * This ratio of CO2 to kilo of device is then used to estimate the footprint of
     * miscellaneous devices that are recorded in the DB.
     */
    public static function getEmissionRatioPowered()
    {
        $result = DB::select(DB::raw("
SELECT SUM(c.footprint) / SUM(COALESCE(c.weight, 0.0)) AS ratio
FROM devices d
JOIN categories c ON c.idcategories = d.category
AND d.repair_status = 1
AND c.powered = 1
"));
        return $result[0]->ratio;
    }

    /**
     * Proposed changes to getWeights().
     * Adds calculation for unpowered categories with lca data.
     * Misc weight/footprint values = 0 in categories table.
     * Removal of hard-coded Misc ids.
     * Separate emission calculations for powered/unpowered devices.
     * Assuming emission ratio calculated separately.
     * PENDING confirmation of assumptions.
     * SEE tests/Feature/FooStatsTest::get_weights_new().
     * */
    public static function getWeights($group = null)
    {
        $sql =
            'SELECT

sum(case when (categories.weight = 0) then (devices.estimate + 0.0) else categories.weight end) as total_weights,
sum(case when (categories.powered = 1) then (case when (categories.weight = 0) then (devices.estimate + 0.0) else categories.weight end) else 0 end) as powered_waste,
sum(case when (categories.powered = 0) then (case when (categories.weight = 0) then (devices.estimate + 0.0) else categories.weight end) else 0 end) as unpowered_waste,
sum(case when (categories.powered = 1) then (case when (categories.weight = 0) then (devices.estimate + 0.0) * @eRatio else categories.footprint * @displacement end) else 0 end) as powered_footprint,
sum(case when (categories.powered = 0) then (case when (categories.weight = 0) then (devices.estimate + 0.0) * @uRatio else categories.footprint * @displacement end) else 0 end) as unpowered_footprint

FROM devices, categories, events,

(select @displacement := :displacement) inner_tbl_displacement,
(select @eRatio := :eRatio) inner_tbl_eratio,
(select @uRatio := :uRatio) inner_tbl_uratio

WHERE devices.category = categories.idcategories and devices.repair_status = 1
AND devices.event = events.idevents ';

        $eRatio = LcaStatsHelper::getEmissionRatioPowered();
        $uRatio = LcaStatsHelper::getEmissionRatioUnpowered();
        $displacement = LcaStatsHelper::getDisplacementFactor();
        $params = ['displacement' => $displacement, 'eRatio' => $eRatio, 'uRatio' => $uRatio];

        if (!is_null($group) && is_numeric($group)) {
            $sql .= ' AND events.group = :group ';
            $params['group'] = $group;

            return DB::select(DB::raw($sql), $params);
        }

        return DB::select(DB::raw($sql), $params);
    }
}
