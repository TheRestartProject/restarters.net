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

    public static function roleProvider(): array {
        return [
            [ 'Administrator' ],
            [ 'NetworkCoordinator' ],
        ];
    }

    /**
     * @dataProvider roleProvider
     */
    public function testMakeHost($role): void
    {
        $user = User::factory()->{lcfirst($role)}()->create();
        $this->actingAs($user);

        if ($role == 'NetworkCoordinator') {
            $this->network->addCoordinator($user);
        }

        $host = User::factory()->create();
        $this->assertEquals(Role::RESTARTER, $host->role);
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

        $response = $this->patch("/api/v2/groups/{$this->idgroups}/volunteers/{$host->id}?api_token=" . $user->api_token, [
            'host' => true,
        ]);
        $response->assertSuccessful();
        $host->refresh();
        $this->assertEquals(Role::HOST, $host->role);

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

    public function testHostMakeHost(): void
    {
        $firsthost = User::factory()->host()->create();
        $this->group->addVolunteer($firsthost);
        $this->group->makeMemberAHost($firsthost);
        $this->actingAs($firsthost);

        $host = User::factory()->host()->create();
        $this->group->addVolunteer($host);

        $response = $this->patch("/api/v2/groups/{$this->idgroups}/volunteers/{$host->id}?api_token=" . $firsthost->api_token);
        $response->assertSuccessful();

        // Remove them.
        $response = $this->delete("/api/v2/groups/{$this->idgroups}/volunteers/{$host->id}?api_token=" . $firsthost->api_token);
        $response->assertSuccessful();

        // Removed host tries and fails to remove the first host.
        $this->actingAs($host);
        $this->expectException(AuthenticationException::class);
        $response = $this->delete("/api/v2/groups/{$this->idgroups}/volunteers/{$host->id}?api_token=" . $host->api_token);
    }

    public static function providerTrueFalse(): array
    {
        return [
            [false],
            [true],
        ];
    }

    /**
     * @dataProvider providerTrueFalse
     */
    public function testNetworkCoordinatorDemoteHost($addToNetwork): void {
        $host = User::factory()->host()->create();
        $this->group->addVolunteer($host);
        $this->group->makeMemberAHost($host);

        $coordinator = User::factory()->networkCoordinator()->create();

        if ($addToNetwork) {
            $this->network->addCoordinator($coordinator);
        } else {
            $this->expectException(AuthenticationException::class);
        }

        $response = $this->patch("/api/v2/groups/{$this->idgroups}/volunteers/{$host->id}?api_token=".$coordinator->api_token, [
            'host' => true,
        ]);

        $response->assertSuccessful();
    }

    public function testAdminCanSeeVolunteerEmail(): void
    {
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $volunteer = User::factory()->create([
            'email' => 'volunteer@example.com',
        ]);
        $this->group->addVolunteer($volunteer);

        $response = $this->get("/api/v2/groups/{$this->idgroups}/volunteers");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);

        // Find the volunteer in the response
        $volunteerData = collect($json['data'])->firstWhere('user', $volunteer->id);
        $this->assertNotNull($volunteerData);
        $this->assertArrayHasKey('email', $volunteerData);
        $this->assertEquals('volunteer@example.com', $volunteerData['email']);
    }

    public function testHostCanSeeVolunteerEmail(): void
    {
        $host = User::factory()->host()->create();
        $this->group->addVolunteer($host);
        $this->group->makeMemberAHost($host);
        $this->actingAs($host);

        $volunteer = User::factory()->create([
            'email' => 'volunteer@example.com',
        ]);
        $this->group->addVolunteer($volunteer);

        $response = $this->get("/api/v2/groups/{$this->idgroups}/volunteers");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);

        // Find the volunteer in the response
        $volunteerData = collect($json['data'])->firstWhere('user', $volunteer->id);
        $this->assertNotNull($volunteerData);
        $this->assertArrayHasKey('email', $volunteerData);
        $this->assertEquals('volunteer@example.com', $volunteerData['email']);
    }

    public function testNetworkCoordinatorCanSeeVolunteerEmail(): void
    {
        $coordinator = User::factory()->networkCoordinator()->create();
        $this->network->addCoordinator($coordinator);
        $this->actingAs($coordinator);

        $volunteer = User::factory()->create([
            'email' => 'volunteer@example.com',
        ]);
        $this->group->addVolunteer($volunteer);

        $response = $this->get("/api/v2/groups/{$this->idgroups}/volunteers");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);

        // Find the volunteer in the response
        $volunteerData = collect($json['data'])->firstWhere('user', $volunteer->id);
        $this->assertNotNull($volunteerData);
        $this->assertArrayHasKey('email', $volunteerData);
        $this->assertEquals('volunteer@example.com', $volunteerData['email']);
    }

    public function testRestarterCannotSeeVolunteerEmail(): void
    {
        $restarter = User::factory()->restarter()->create();
        $this->group->addVolunteer($restarter);
        $this->actingAs($restarter);

        $volunteer = User::factory()->create([
            'email' => 'volunteer@example.com',
        ]);
        $this->group->addVolunteer($volunteer);

        $response = $this->get("/api/v2/groups/{$this->idgroups}/volunteers");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);

        // Find the volunteer in the response
        $volunteerData = collect($json['data'])->firstWhere('user', $volunteer->id);
        $this->assertNotNull($volunteerData);
        $this->assertArrayNotHasKey('email', $volunteerData);
    }
}
