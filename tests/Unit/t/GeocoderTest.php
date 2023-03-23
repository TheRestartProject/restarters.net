<?php

namespace Tests\Unit\t;

use Tests\TestCase;

class GeocoderTest extends TestCase
{
    public function testGeocode() {
        $geocoder = new \App\Helpers\Geocoder();
        $ret = $geocoder->geocode('6 Canterbury Crescent, London SW9 7QD');
        echo "Geocoded to " . json_encode($ret) . "\n";
        $this->assertEquals($ret['latitude'], 51.46398);
        $this->assertEquals($ret['longitude'], -0.11302);
        $this->assertEquals($ret['country'], 'United Kingdom');
    }
}
