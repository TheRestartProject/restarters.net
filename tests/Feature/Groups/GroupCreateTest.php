<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Notifications\GroupConfirmed;
use App\Role;
use App\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class GroupCreateTest extends TestCase
{
    public function testCreate()
    {
        $user = factory(User::class)->states('Administrator')->create([
                                                                      'api_token' => '1234',
                                                                  ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup();
        $this->assertNotNull($idgroups);
        $group = Group::find($idgroups);

        $response = $this->get('/api/groups?api_token=1234');
        $response->assertSuccessful();
        $ret = json_decode($response->getContent(), TRUE);
        self::assertEquals(1, count($ret));
        self::assertEquals($idgroups, $ret[0]['idgroups']);
        self::assertEquals($group->name, $ret[0]['name']);
    }

    public function testCreateBadLocation()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Use an address which will fail to geocode.
        $this->assertNull($this->createGroup('Test Group', 'https://therestartproject.org', 'zzzzzzzzzzz123', 'Some text', false));
        $this->assertContains(__('groups.geocode_failed'), $this->lastResponse->getContent());
    }

    public function testDuplicate()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Test creating the same group twice.
        $this->assertNotNull($this->createGroup());

        $response = $this->post('/group/create', [
            'name' => 'Test Group0',
            'website' => 'https://therestartproject.org',
            'location' => 'London',
            'free_text' => 'Some text.',
        ]);

        $this->assertContains('That group name (Test Group0) already exists', $response->getContent());
    }

    public function testApprove() {
        Notification::fake();

        $admin1 = factory(User::class)->state('Administrator')->create();
        $this->actingAs($admin1);

        $idgroups = $this->createGroup('Test Group');
        $group = Group::find($idgroups);

        $admin2 = factory(User::class)->state('Administrator')->create();
        $this->actingAs($admin2);

        $response = $this->post('/group/edit/'.$idgroups, [
            'description' => 'Test',
            'location' => 'London',
            'name' => $group->name,
            'website' => 'https://therestartproject.org',
            'free_text' => 'HQ',
            'moderate' => 'approve',
            'area' => 'London',
            'postcode' => 'SW9 7QD'
        ]);

        Notification::assertSentTo(
            [$admin1],
            GroupConfirmed::class,
            function ($notification, $channels, $host) use ($group) {
                $mailData = $notification->toMail($host)->toArray();
                self::assertEquals(__('notifications.group_confirmed_subject', [], $host->language), $mailData['subject']);

                // Mail should mention the group name.
                self::assertRegexp('/' . $group->name . '/', $mailData['introLines'][0]);

                return true;
            }
        );

        $this->assertContains('Group updated!', $response->getContent());
    }
}
