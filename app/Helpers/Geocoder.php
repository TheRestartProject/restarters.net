<?php

namespace App\Helpers;


class Geocoder
{
    public function __construct()
    {
    }

    public function geocode($location)
    {
        $json = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($location)."&key=AIzaSyDb1_XdeHbwLg-5Rr3EOHgutZfqaRp8THE");

        $decoded = json_decode($json)->results[0];

        $latitude = $decoded->{'geometry'}->{'location'}->lat;
        $longitude = $decoded->{'geometry'}->{'location'}->lng;

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }
}
