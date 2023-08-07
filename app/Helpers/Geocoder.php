<?php

namespace App\Helpers;

use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\Provider\Mapbox\Mapbox;

class Geocoder
{
    public function geocode($location)
    {
        if ($location != 'ForceGeocodeFailure') {
            $geocodeResponse = app('geocoder')->geocodeQuery(GeocodeQuery::create($location)->withData('location_type', [ Mapbox::TYPE_PLACE, Mapbox::TYPE_ADDRESS ]));
            $addressCollection = $geocodeResponse->get();
            $address = $addressCollection->get(0);

            if ($address) {
                // We are returned the name, but we want the code.
                $countries = array_flip(\App\Helpers\Fixometer::getAllCountries('en'));

                return [
                    'latitude' => $address->getCoordinates()->getLatitude(),
                    'longitude' => $address->getCoordinates()->getLongitude(),
                    'country_code' => $countries[$address->getCountry()->getName()]
                ];
            }
        }

        return false;
    }
}
