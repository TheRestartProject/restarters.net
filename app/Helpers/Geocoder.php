<?php

namespace App\Helpers;

class Geocoder
{
    public function __construct()
    {
    }

    private function googleKey()
    {
        // We have this so that we can change the key in testing.
        return config('GOOGLE_API_CONSOLE_KEY') ?? env('GOOGLE_API_CONSOLE_KEY');
    }

    public function geocode($location)
    {
        if ($location != 'ForceGeocodeFailure') {
            $json = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($location).'&key='.$this->googleKey());

            if ($json) {
                $res = json_decode($json);

                if ($res && $res->results && count($res->results)) {
                    $decoded = json_decode($json)->results[0];

                    $latitude = $decoded->{'geometry'}->{'location'}->lat;
                    $longitude = $decoded->{'geometry'}->{'location'}->lng;

                    foreach ($decoded->{'address_components'} as $component) {
                        if ($component->types && count($component->types) && $component->types[0] === 'country') {
                            $country_code = $component->short_name;
                        }
                    }

                    return [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'country_code' => $country_code,
                    ];
                }
            }
        }

        return false;
    }

    public function reverseGeocode($lat, $lng)
    {
        $json = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lng&key=".$this->googleKey());

        $decoded = json_decode($json)->results[0];

        return $decoded;
    }
}
