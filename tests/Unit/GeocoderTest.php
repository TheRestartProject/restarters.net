<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;

use App\Helpers\Geocoder;

/**
 *
 */
class GoogleGeocoderTest extends TestCase
{

    public function testCanGeocodeAddress()
    {
        $stub = $this->createMock(Geocoder::class);
        $stub->method('geocode')->willReturn('foo');
        $this->assertEquals('foo', $stub->geocode('London'));
    }
}