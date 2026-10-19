<?php

namespace Tests\Feature\Networks;

use App\Group;
use App\Network;
use App\User;
use Tests\TestCase;

class NetworkPageVisibilityTest extends TestCase
{
    private function networkData($response): array
    {
        return $this->vueProp($response, ':network');
    }

    private function vueProp($response, string $name)
    {
        foreach ($this->getVueProperties($response) as $prop) {
            if (array_key_exists($name, $prop)) {
                return json_decode($prop[$name], true);
            }
        }

        $this->fail("No $name property on the page");
    }

    private function networkWithHost(): array
    {
        $network = Network::factory()->create(['reporting_url' => 'https://reports.example.org/dashboard/7']);
        $group = Group::factory()->create(['approved' => true]);
        $network->addGroup($group);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        return [$network, $group, $host];
    }

    public function testHostOfGroupInNetworkSeesPageWithoutCoordinatorData(): void
    {
        [$network, , $host] = $this->networkWithHost();

        $coordinator = User::factory()->networkCoordinator()->create(['name' => 'Cordelia Coordinator']);
        $network->addCoordinator($coordinator);

        $this->actingAs($host);
        $response = $this->get('/networks/' . $network->id);
        $response->assertStatus(200);

        $data = $this->networkData($response);
        $this->assertNull($data['reporting_url']);
        $this->assertEmpty($data['coordinators']);
        $this->assertFalse($this->vueProp($response, ':can-moderate'));
        $this->assertFalse($this->vueProp($response, ':can-manage-tags'));
        $this->assertFalse($this->vueProp($response, ':can-associate-groups'));
        $this->assertEmpty($this->vueProp($response, ':initial-tags'));

        $response->assertDontSee('Cordelia Coordinator');
        $response->assertDontSee('reports.example.org');
        $response->assertDontSee('network-add-group');
    }

    public function testHostOfUnrelatedNetworkGroupIsForbidden(): void
    {
        [$network] = $this->networkWithHost();
        [, , $otherHost] = $this->networkWithHost();

        $this->actingAs($otherHost);
        $this->get('/networks/' . $network->id)->assertStatus(403);
    }

    public function testRestarterInGroupIsForbidden(): void
    {
        [$network, $group] = $this->networkWithHost();

        $member = User::factory()->restarter()->create();
        $group->addVolunteer($member);

        $this->actingAs($member);
        $this->get('/networks/' . $network->id)->assertStatus(403);
    }

    public function testCoordinatorStillGetsFullPage(): void
    {
        [$network] = $this->networkWithHost();

        $coordinator = User::factory()->networkCoordinator()->create(['name' => 'Cordelia Coordinator']);
        $network->addCoordinator($coordinator);

        $this->actingAs($coordinator);
        $response = $this->get('/networks/' . $network->id);
        $response->assertStatus(200);

        $data = $this->networkData($response);
        $this->assertEquals('https://reports.example.org/dashboard/7', $data['reporting_url']);
        $this->assertCount(1, $data['coordinators']);
        $this->assertTrue($this->vueProp($response, ':can-moderate'));
        $this->assertTrue($this->vueProp($response, ':can-manage-tags'));
    }

    public function testAdministratorStillGetsFullPage(): void
    {
        [$network] = $this->networkWithHost();

        $this->actingAs(User::factory()->administrator()->create());
        $response = $this->get('/networks/' . $network->id);
        $response->assertStatus(200);
        $this->assertTrue($this->vueProp($response, ':can-moderate'));
    }
}
