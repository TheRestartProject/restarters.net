<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

/**
 * Google Geocoder interface.
 */
class Geocoder
{

    protected $apikey = "AIzaSyDb1_XdeHbwLg-5Rr3EOHgutZfqaRp8THE";

    /**
     * Geocode an address.
     *
     * @return the latitude and longitude
     */
    public function geocode($location)
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=";
        $url .= urlencode($location.',United Kingdom');
        $url .= "&key=AIzaSyDb1_XdeHbwLg-5Rr3EOHgutZfqaRp8THE";
        print($url);
        try {
            $json = file_get_contents($url);
            $json = json_decode($json);
            if (is_object($json) && !empty($json->{'results'})) {
                $latitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lat;
                $longitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lng;
            }
        } catch (\Exception $ex) {
            $latitude = 0;
            $longitude = 0;
            Log::error('An error occurred during geocoding: ' . $ex->getMessage());
        }
        return [
            'lat' => $latitude,
            'lon' => $longitude
        ];
    }
}