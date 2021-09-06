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
    public function testPageLoads()
    {
        // Test the dashboard page loads.  Most of the work is done inside Vue, so a basic test is just that the
        // Vue component exists.
        $this->loginAsTestUser();
        $response = $this->get('/dashboard');

        $this->assertVueProperties($response, [
            [
                'administrator' => 'false',
                'host' => 'false',
                'restarter' => 'true',
                'network-coordinator' => 'false',
                ':your-groups' => '[]',
                ':nearby-groups' => '[]',
                ':upcoming-events' => '[]',
                ':past-events' => 'null',
                ':topics' => '[]',
                'see-all-topics-link' => env('DISCOURSE_URL').'/latest',
                ':is-logged-in' => 'true',
                'discourse-base-url' => env('DISCOURSE_URL'),
                ':new-groups' => '0',
            ],
        ]);
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
