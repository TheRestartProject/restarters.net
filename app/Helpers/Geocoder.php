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
                return [
                    'latitude' => $address->getCoordinates()->getLatitude(),
                    'longitude' => $address->getCoordinates()->getLongitude(),
                    'country' => $address->getCountry()->getName()
                ];
            }
        }

        return false;
    }
}
