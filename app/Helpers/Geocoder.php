<?php

namespace App\Helpers;


class Geocoder
{
    public function __construct()
    {
    }

    public function geocode($location)
    {
        $json = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($location)."&key=" . env('GOOGLE_API_CONSOLE_KEY'));

        $decoded = json_decode($json)->results[0];

        $latitude = $decoded->{'geometry'}->{'location'}->lat;
        $longitude = $decoded->{'geometry'}->{'location'}->lng;

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    public function reverseGeocode($lat, $lng)
    {
        $json = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lng&key=" . env('GOOGLE_API_CONSOLE_KEY'));

        $decoded = json_decode($json)->results[0];

        return $decoded;
    }

}
