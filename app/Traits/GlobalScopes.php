<?php

namespace App\Traits;

use Carbon\Carbon;
use DB;

trait GlobalScopes
{
    /**
     * From the start of last month till now.
     * @author Christopher Kelker 2020-02-18
     * @return $query
     */
    public function scopeCreatedWithinLastMonth($query)
    {
        return $query->whereBetween('created_at', [
          Carbon::now()->submonth()->endOfMonth(),
          Carbon::now(),
        ]);
    }

    /**
     * Query columns by lat and long distance by kilometers
     * @author Christopher Kelker 2020-02-18
     * @return $query
     */
    public function scopeHavingDistanceWithin($query, int $kilometers, array $coordinates = null)
    {
        $latitude = auth()->user()->latitude;
        $longitude = auth()->user()->longitude;

        if (! is_null($coordinates)) {
            $latitude = $coordinates['latitude'];
            $longitude = $coordinates['longitude'];
        }

        return $query->select(DB::raw('`'.$this->getTable().'`.*, ( 6371 * acos( cos( radians('.$latitude.') ) * cos( radians( '.$this->getTable().'.latitude ) ) * cos( radians( '.$this->getTable().'.longitude ) - radians('.$longitude.') ) + sin( radians('.$latitude.') ) * sin( radians( '.$this->getTable().'.latitude ) ) ) ) AS distance'))
        ->having('distance', '<=', $kilometers);
    }
}
