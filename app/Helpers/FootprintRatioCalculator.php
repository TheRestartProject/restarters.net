<?php

namespace App\Helpers;

use DB;

/**
 * Calculates the average footprint per kilo of fixed device.
 *
 * Calculated by looking at all of the fixed, non-misc devices in the database,
 * and dividing the total footprint of these devices by the total weight of these
 * devices.
 *
 * This ratio of CO2 to kilo of device is then used to estimate the footprint of
 * miscellaneous devices that are recorded in the DB.
 */
class FootprintRatioCalculator
{
    public function calculateRatio()
    {
        // TODO: parameterise repair_status value and id for misc category using env values.
        $result = DB::select(DB::raw('
select @ratio as `emission_ratio`
from
(select @ratio := sum(`categories`.`footprint`) / sum(`categories`.`weight` + 0.0)
from `devices`, `categories`
where
 `categories`.`idcategories` = `devices`.`category` and `devices`.`repair_status` = 1 and categories.idcategories != 46
) inner_tbl'));

        return $result[0]->emission_ratio;
    }
}

