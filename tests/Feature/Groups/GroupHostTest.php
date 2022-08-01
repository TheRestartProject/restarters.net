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
        $this->network = factory(Network::class)->create();
        $this->network->addGroup($this->group);
        $this->assertNotNull($this->idgroups);
    }

    public function roleProvider() {
        return [
            [ 'Administrator' ],
            [ 'NetworkCoordinator' ],
        ];
    }

    /**
     * @dataProvider roleProvider
     */
    public function testVolunteerNotInGroup($role)
    {
        $user = factory(User::class)->states($role)->create();
        $this->actingAs($user);

        if ($role == 'NetworkCoordinator') {
            $this->network->addCoordinator($user);
        }

        $host = factory(User::class)->states('Host')->create();

        try {
            $response = $this->get("/group/make-host/{$this->idgroups}/{$host->id}");
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertContains('Volunteer is not currently in this group', $e->getMessage());
        }
    }

    /**
     * @dataProvider roleProvider
     */
    public function testMakeHost($role)
    {
        $user = factory(User::class)->states($role)->create();
        $this->actingAs($user);

        if ($role == 'NetworkCoordinator') {
            $this->network->addCoordinator($user);
        }

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
