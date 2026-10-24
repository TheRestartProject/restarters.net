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

    public function testHostOfGroupInNetworkSeesPageWithoutCoordinatorOnlyData(): void
    {
        [$network, , $host] = $this->networkWithHost();

        $coordinator = User::factory()->networkCoordinator()->create(['name' => 'Cordelia Coordinator']);
        $network->addCoordinator($coordinator);

        $this->actingAs($host);
        $response = $this->get('/networks/' . $network->id);
        $response->assertStatus(200);

        $data = $this->networkData($response);
        $this->assertNull($data['reporting_url']);

        // Organisers can see who their coordinators are.
        $this->assertCount(1, $data['coordinators']);
        $this->assertEquals('Cordelia Coordinator', $data['coordinators'][0]['name']);
        $this->assertArrayHasKey('picture', $data['coordinators'][0]);
        $this->assertFalse($this->vueProp($response, ':can-moderate'));
        $this->assertFalse($this->vueProp($response, ':can-manage-tags'));
        $this->assertFalse($this->vueProp($response, ':can-associate-groups'));
        $this->assertEmpty($this->vueProp($response, ':initial-tags'));

        $response->assertDontSee('reports.example.org');
        $response->assertDontSee('network-add-group');
    }

    public function testHostOfUnrelatedNetworkGroupIsForbidden(): void
    {
        [$network] = $this->networkWithHost();
        [, , $otherHost] = $this->networkWithHost();

        $this->actingAs($otherHost);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->get('/networks/' . $network->id);
    }

    public function testVolunteerInGroupSeesPageWithoutCoordinatorOnlyData(): void
    {
        [$network, $group] = $this->networkWithHost();
        $network->addCoordinator(User::factory()->networkCoordinator()->create(['name' => 'Cordelia Coordinator']));

        $member = User::factory()->restarter()->create();
        $group->addVolunteer($member);

        $this->actingAs($member);
        $response = $this->get('/networks/' . $network->id);
        $response->assertStatus(200);
        $this->assertNull($this->networkData($response)['reporting_url']);
        $this->assertFalse($this->vueProp($response, ':can-moderate'));
        $response->assertDontSee('reports.example.org');
    }

    public function testUserWithNoGroupInNetworkIsForbidden(): void
    {
        [$network] = $this->networkWithHost();

        $this->actingAs(User::factory()->restarter()->create());
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->get('/networks/' . $network->id);
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

    private function groupAt(Network $network, string $name, float $lat, float $lng, array $extra = []): Group
    {
        $group = Group::factory()->create(array_merge(['name' => $name, 'approved' => true, 'latitude' => $lat, 'longitude' => $lng], $extra));
        $network->addGroup($group);

        return $group;
    }

    public function testHostSeesTheFiveNearestGroupsInTheNetwork(): void
    {
        [$network, $group, $host] = $this->networkWithHost();
        $group->update(['latitude' => 51.50, 'longitude' => -0.10]);

        // Six candidates at increasing distances, plus groups that must not be listed.
        foreach ([1, 2, 3, 4, 5, 6] as $i) {
            $this->groupAt($network, "Near $i", 51.50 + $i * 0.05, -0.10);
        }
        $this->groupAt($network, 'Unapproved', 51.501, -0.10, ['approved' => false]);
        $this->groupAt($network, 'Archived', 51.501, -0.10, ['archived_at' => '2020-01-01']);
        Group::factory()->create(['name' => 'Other network', 'approved' => true, 'latitude' => 51.501, 'longitude' => -0.10]);

        $this->actingAs($host);
        $response = $this->get('/networks/' . $network->id);
        $response->assertStatus(200);

        $nearby = $this->vueProp($response, ':nearby-groups');
        $this->assertEquals($group->name, $nearby['from']);
        $this->assertEquals(['Near 1', 'Near 2', 'Near 3', 'Near 4', 'Near 5'], array_column($nearby['groups'], 'name'));
        $this->assertEqualsWithDelta(5.6, $nearby['groups'][0]['distance'], 0.2);
    }

    public function testUserInSeveralGroupsSeesNearestToAnyOfThemOnce(): void
    {
        [$network, $group, $host] = $this->networkWithHost();
        $group->update(['latitude' => 51.50, 'longitude' => -0.10]);

        $second = $this->groupAt($network, 'Second home', 53.00, -0.10);
        $second->addVolunteer($host);

        // One near each of the user's groups, and one far from both.
        $this->groupAt($network, 'By first', 51.55, -0.10);
        $this->groupAt($network, 'By second', 53.03, -0.10);
        $this->groupAt($network, 'Far away', 56.00, -0.10);

        $this->actingAs($host);
        $nearby = $this->vueProp($this->get('/networks/' . $network->id), ':nearby-groups');

        $names = array_column($nearby['groups'], 'name');
        $this->assertEquals(['By second', 'By first', 'Far away'], $names);
        $this->assertCount(count(array_unique($names)), $names);
        $this->assertNotContains('Second home', $names);
        $this->assertNotContains($group->name, $names);
        $this->assertEqualsWithDelta(3.3, $nearby['groups'][0]['distance'], 0.2);
        $this->assertEqualsWithDelta(5.6, $nearby['groups'][1]['distance'], 0.2);
    }

    public function testNoNearbyGroupsWithoutAGroupLocation(): void
    {
        [$network, $group, $host] = $this->networkWithHost();
        $group->update(['latitude' => null, 'longitude' => null]);
        $this->groupAt($network, 'Somewhere', 51.5, -0.1);

        $this->actingAs($host);
        $this->assertNull($this->vueProp($this->get('/networks/' . $network->id), ':nearby-groups'));

        // A coordinator with no group of their own gets no nearby section either.
        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);
        $this->assertNull($this->vueProp($this->get('/networks/' . $network->id), ':nearby-groups'));
    }
}
