<?php

namespace Tests\Unit\t;

use Tests\TestCase;

class GeocoderTest extends TestCase
{
    public function testGeocode() {
        $geocoder = new \App\Helpers\Geocoder();
        $ret = $geocoder->geocode('6 Canterbury Crescent, London SW9 7QD');
        $this->assertEquals(51.4643585, $ret['latitude']);
        $this->assertEquals(-0.1135401, $ret['longitude']);
        $this->assertEquals('GB', $ret['country_code']);
    }
}
