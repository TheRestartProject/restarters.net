<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Network;
use App\Role;
use App\Skills;
use App\User;
use App\UsersSkills;
use Tests\TestCase;
use Illuminate\Auth\AuthenticationException;

class GroupHostTest extends TestCase
{
    private $idgroups = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $this->idgroups = $this->createGroup();
        $this->group = Group::find($this->idgroups);
        $this->network = Network::factory()->create();
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
        $user = User::factory()->{lcfirst($role)}()->create();
        $this->actingAs($user);

        if ($role == 'NetworkCoordinator') {
            $this->network->addCoordinator($user);
        }

        $host = User::factory()->host()->create();

        try {
            $response = $this->get("/group/make-host/{$this->idgroups}/{$host->id}");
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Volunteer is not currently in this group', $e->getMessage());
        }
    }

    /**
     * @dataProvider roleProvider
     */
    public function testMakeHost($role)
    {
        $user = User::factory()->{lcfirst($role)}()->create();
        $this->actingAs($user);

        if ($role == 'NetworkCoordinator') {
            $this->network->addCoordinator($user);
        }

        $host = User::factory()->host()->create();
        $this->group->addVolunteer($host);

        $skill1 = Skills::create([
            'skill_name'  => 'UT1',
            'description' => 'Planning',
            'category' => 1
        ]);

        UsersSkills::create([
            'skill' => $skill1->id,
            'user' => $host->id,
        ]);

        // Get the volunteers.  There will be an admin added by the createGroup() method, so the host we've just
        // added will be the second.
        $response = $this->get("/api/v2/groups/{$this->idgroups}/volunteers");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(2, count($json['data']));
        $this->assertEquals($host->id, $json['data'][1]['user']);
        $this->assertFalse($json['data'][1]['host']);
        $this->assertEquals(1, count($json['data'][1]['skills']));

        $response = $this->get("/group/make-host/{$this->idgroups}/{$host->id}");
        $response->assertSessionHas('success');

        $response = $this->get("/api/v2/groups/{$this->idgroups}/volunteers");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(2, count($json['data']));
        $this->assertEquals($host->id, $json['data'][1]['user']);
        $this->assertTrue($json['data'][1]['host']);

        // Remove them.
        $response = $this->delete("/api/v2/groups/{$this->idgroups}/volunteers/{$host->id}?api_token=" . $host->api_token);
        $response->assertSuccessful();

        $response = $this->get("/api/v2/groups/{$this->idgroups}/volunteers");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($json['data']));
    }

    public function testHostMakeHost()
    {
        $firsthost = User::factory()->host()->create();
        $this->group->addVolunteer($firsthost);
        $this->group->makeMemberAHost($firsthost);
        $this->actingAs($firsthost);

        $host = User::factory()->host()->create();
        $this->group->addVolunteer($host);

        $response = $this->get("/group/make-host/{$this->idgroups}/{$host->id}");
        $response->assertSessionHas('success');

        // Remove them.
        $response = $this->delete("/api/v2/groups/{$this->idgroups}/volunteers/{$host->id}?api_token=" . $firsthost->api_token);
        $response->assertSuccessful();

        // Removed host tries and fails to remove the first host.
        $this->actingAs($host);
        $this->expectException(AuthenticationException::class);
        $response = $this->delete("/api/v2/groups/{$this->idgroups}/volunteers/{$host->id}?api_token=" . $host->api_token);
    }

    public function testIrrelevantHost()
    {
        $firsthost = User::factory()->host()->create();
        $this->actingAs($firsthost);

        $host = User::factory()->host()->create();
        $this->group->addVolunteer($host);

        $response = $this->get("/group/make-host/{$this->idgroups}/{$host->id}");
        $response->assertSessionHas('warning');
    }
}
