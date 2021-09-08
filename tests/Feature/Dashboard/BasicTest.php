<?php

namespace Tests\Feature\Dashboard;

use App\Group;
use App\Party;
use App\Role;
use App\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase
{
    /**
     *@dataProvider provider
     */
    public function testPageLoads($city, $country, $lat, $lng, $nearbyGroupCount)
    {
        // Test the dashboard page loads and shows a nearby group when relevant.
        $user = factory(User::class)->states('Host')->create();
        $this->actingAs($user);
        $this->createGroup();

        $user = factory(User::class)->states('Restarter')->create();
        $user->update([
            'location' => $city,
            'country' => $country,
            'latitude' => $lat,
            'longitude' => $lng
            ]);
        $user->save();
        $user->refresh();
        $this->assertEquals($user->country, $country);
        $this->assertEquals($user->location, $city);
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $props = $this->assertVueProperties($response, [
            [
                'administrator' => 'false',
                'host' => 'false',
                'restarter' => 'true',
                'network-coordinator' => 'false',
                'location' => "$city",
                ':your-groups' => '[]',
                ':upcoming-events' => '[]',
                ':past-events' => 'null',
                ':topics' => '[]',
                'see-all-topics-link' => env('DISCOURSE_URL').'/latest',
                ':is-logged-in' => 'true',
                'discourse-base-url' => env('DISCOURSE_URL'),
                ':new-groups' => '0',
            ],
        ]);

        $this->assertEquals($nearbyGroupCount, count(json_decode($props[0][':nearby-groups'], TRUE)));
    }

    public function provider() {
        return [
            [ 'London', 'GB', 51.5465, -0.10581, 1 ],    // Known location, nearby group
            [ null, 'GB', null, null, 0 ],               // Unknown location, no nearby group
            [ 'Lima', 'PE', -12.0464, -77.04280, 0 ],    // Known location, no nearby group
            [ null, 'PE', null, null, 0 ]                // Unknown location, no nearby group
        ];
    }

    public function testUpcomingEvents() {
        // Create a group with a future event, and join it.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();

        // Admin approves the event.
        $event = factory(Party::class)->create([
           'group' => $id,
           'event_date' => '2130-01-01',
           'start' => '12:13',
           'free_text' => 'A test event'
       ]);


        $eventData = $event->getAttributes();
        $eventData['wordpress_post_id'] = 100;
        $eventData['id'] = $event->idevents;
        $eventData['moderate'] = 'approve';
        $this->post('/party/edit/' . $event->idevents, $eventData);

        // Get the dashboard
        $host = factory(User::class)->states('Restarter')->create();
        $this->actingAs($host);
        $this->get('/group/join/' . $id);

        $response = $this->get('/dashboard');
        $response->assertSee('A test event');
    }
}
