<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Network;
use App\Role;
use App\User;
use Tests\TestCase;

class GroupHostTest extends TestCase
{
    private $idgroups = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $this->idgroups = $this->createGroup();
        $this->group = Group::find($this->idgroups);
        $this->assertNotNull($this->idgroups);
    }

    public function testVolunteerNotInGroup()
    {
        $user = factory(User::class)->states('Administrator')->create();
        $this->actingAs($user);

        $host = factory(User::class)->states('Host')->create();

        try {
            $response = $this->get("/group/make-host/{$this->idgroups}/{$host->id}");
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertContains('Volunteer is not currently in this group', $e->getMessage());
        }
    }

    public function testAdministratorMakeHost()
    {
        $user = factory(User::class)->states('Administrator')->create();
        $this->actingAs($user);

        $host = factory(User::class)->states('Host')->create();
        $this->group->addVolunteer($host);

        $response = $this->get("/group/make-host/{$this->idgroups}/{$host->id}");
        $response->assertSessionHas('success');
    }

    public function testNetworkCoordinatorMakeHost()
    {
        $network = factory(Network::class)->create();
        $coordinator = factory(User::class)->state('NetworkCoordinator')->create();
        $network->addGroup($this->group);
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);

        $host = factory(User::class)->states('Host')->create();
        $this->group->addVolunteer($host);

        $response = $this->get("/group/make-host/{$this->idgroups}/{$host->id}");
        $response->assertSessionHas('success');
    }

    public function testHostMakeHost()
    {
        $firsthost = factory(User::class)->states('Host')->create();
        $this->group->addVolunteer($firsthost);
        $this->group->makeMemberAHost($firsthost);
        $this->actingAs($firsthost);

        $host = factory(User::class)->states('Host')->create();
        $this->group->addVolunteer($host);

        $response = $this->get("/group/make-host/{$this->idgroups}/{$host->id}");
        $response->assertSessionHas('success');

        // Remove them.
        $response = $this->get("/group/remove-volunteer/{$this->idgroups}/{$host->id}");
        $response->assertSessionHas('success');

        // Remove them again - should redirect back with warning.
        $response = $this->from('/')->get("/group/remove-volunteer/{$this->idgroups}/{$host->id}");
        $response->assertRedirect('/');
        $response->assertSessionHas('warning');

        // Removed host tries and fails to remove the first host.
        $response = $this->get('/logout');
        $this->actingAs($host);
        $response = $this->from('/')->get("/group/remove-volunteer/{$this->idgroups}/{$firsthost->id}");
        $response->assertRedirect('/');
        $response->assertSessionHas('warning');
    }

    public function testIrrelevantHost()
    {
        $firsthost = factory(User::class)->states('Host')->create();
        $this->actingAs($firsthost);

        $host = factory(User::class)->states('Host')->create();
        $this->group->addVolunteer($host);

        $response = $this->get("/group/make-host/{$this->idgroups}/{$host->id}");
        $response->assertSessionHas('warning');
    }
}
