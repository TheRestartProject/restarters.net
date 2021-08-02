<?php

namespace App\Helpers;

class Geocoder
{
    public function __construct()
    {
    }

    public function geocode($location)
    {
        $json = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($location).'&key='.env('GOOGLE_API_CONSOLE_KEY'));

        if ($json) {
            $res = json_decode($json);

            if ($res && $res->results && count($res->results)) {
                $decoded = json_decode($json)->results[0];

                $latitude = $decoded->{'geometry'}->{'location'}->lat;
                $longitude = $decoded->{'geometry'}->{'location'}->lng;

                foreach ($decoded->{'address_components'} as $component) {
                    if ($component->types && count($component->types) && $component->types[0] === 'country') {
                        $country = $component->long_name;
                    }
                }

                return [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'country' => $country,
                ];
            }
        }

        return false;
    }

    public function reverseGeocode($lat, $lng)
    {
        $json = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lng&key=".env('GOOGLE_API_CONSOLE_KEY'));

        $decoded = json_decode($json)->results[0];

        return $decoded;
    }
}
