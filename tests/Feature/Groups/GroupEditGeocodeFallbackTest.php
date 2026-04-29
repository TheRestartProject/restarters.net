<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Helpers\Geocoder;
use App\User;
use Tests\TestCase;

class GeocoderFailMock extends Geocoder
{
    public function __construct() {}

    public function geocode($location)
    {
        return null;
    }
}

class GroupEditGeocodeFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(Geocoder::class, function () {
            return new GeocoderFailMock();
        });
    }

    public function testEditGroupWithLocationWhenGeocodingFails(): void
    {
        $group = Group::factory()->create([
            'latitude' => 51.5,
            'longitude' => -0.1,
            'country_code' => 'GB',
            'location' => 'London',
        ]);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        $response = $this->patch('/api/v2/groups/' . $group->idgroups, [
            'name' => $group->name,
            'location' => 'London',
            'description' => 'Updated description',
            'website' => 'https://therestartproject.org',
        ]);

        $response->assertOk();

        $group->refresh();
        $this->assertEquals(51.5, $group->latitude);
        $this->assertEquals(-0.1, $group->longitude);
        $this->assertEquals('GB', $group->country_code);
    }

    public function testEditGroupWithNoLocationWhenGeocodingFails(): void
    {
        $group = Group::factory()->create([
            'latitude' => 51.5,
            'longitude' => -0.1,
        ]);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        $response = $this->patch('/api/v2/groups/' . $group->idgroups, [
            'name' => $group->name,
            'description' => 'Updated description',
            'website' => 'https://therestartproject.org',
        ]);

        $response->assertOk();
    }
}
