<?php

namespace Tests\Feature\Networks;

use App\Group;
use App\Network;
use App\User;
use Tests\TestCase;

/**
 * Who can open a network page, and what they get: GET /api/v2/networks/{id}/viewer gates the page, and the
 * network resource keeps the reporting dashboard for coordinators and administrators.
 */
class NetworkPageVisibilityTest extends TestCase
{
    private const REPORTS = 'https://reports.example.org/dashboard/7';

    private function viewer(?User $user, Network $network)
    {
        if ($user) {
            $this->actingAs($user);
        }

        return $this->getJson('/api/v2/networks/' . $network->id . '/viewer' . ($user ? '?api_token=' . $user->api_token : ''));
    }

    private function networkWithHost(): array
    {
        $network = Network::factory()->create(['reporting_url' => self::REPORTS]);
        $group = Group::factory()->create(['approved' => true]);
        $network->addGroup($group);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        return [$network, $group, $host];
    }

    private function groupAt(Network $network, string $name, float $lat, float $lng, array $extra = []): Group
    {
        $group = Group::factory()->create(array_merge(['name' => $name, 'approved' => true, 'latitude' => $lat, 'longitude' => $lng], $extra));
        $network->addGroup($group);

        return $group;
    }

    public function testHostOfGroupInNetworkCanOpenTheNetworkWithoutModeration(): void
    {
        [$network, , $host] = $this->networkWithHost();

        $this->viewer($host, $network)
            ->assertSuccessful()
            ->assertJsonPath('data.can_moderate', false);
    }

    public function testVolunteerInGroupCanOpenTheNetworkWithoutModeration(): void
    {
        [$network, $group] = $this->networkWithHost();

        $member = User::factory()->restarter()->create();
        $group->addVolunteer($member);

        $this->viewer($member, $network)
            ->assertSuccessful()
            ->assertJsonPath('data.can_moderate', false);
    }

    public function testUserWithNoGroupInNetworkIsForbidden(): void
    {
        [$network] = $this->networkWithHost();
        [, , $otherHost] = $this->networkWithHost();

        $this->viewer($otherHost, $network)->assertStatus(403);
        $this->viewer(User::factory()->restarter()->create(), $network)->assertStatus(403);
    }

    public function testGuestIsRejected(): void
    {
        [$network] = $this->networkWithHost();

        $this->viewer(null, $network)->assertStatus(401);
    }

    public function testCoordinatorAndAdministratorCanModerate(): void
    {
        [$network] = $this->networkWithHost();

        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);

        $this->viewer($coordinator, $network)->assertSuccessful()->assertJsonPath('data.can_moderate', true);
        $this->viewer(User::factory()->administrator()->create(), $network)->assertSuccessful()->assertJsonPath('data.can_moderate', true);
    }

    public function testCoordinatorOfAnotherNetworkIsForbidden(): void
    {
        [$network] = $this->networkWithHost();
        $other = Network::factory()->create();

        $coordinator = User::factory()->networkCoordinator()->create();
        $other->addCoordinator($coordinator);

        $this->viewer($coordinator, $network)->assertStatus(403);
    }

    public function testReportingUrlIsOnlyForCoordinatorsAndAdministrators(): void
    {
        [$network, , $host] = $this->networkWithHost();
        $url = '/api/v2/networks/' . $network->id;

        $this->actingAs($host);
        $this->getJson($url)->assertSuccessful()->assertJsonPath('data.reporting_url', null);

        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);
        $this->getJson($url)->assertJsonPath('data.reporting_url', self::REPORTS);

        $this->actingAs(User::factory()->administrator()->create());
        $this->getJson($url)->assertJsonPath('data.reporting_url', self::REPORTS);
    }

    public function testCoordinatorsAreVisibleToAGroupMember(): void
    {
        [$network, , $host] = $this->networkWithHost();
        $network->addCoordinator(User::factory()->networkCoordinator()->create(['name' => 'Cordelia Coordinator']));

        $this->actingAs($host);
        $response = $this->getJson('/api/v2/networks/' . $network->id)->assertSuccessful();

        $this->assertCount(1, $response->json('data.coordinators'));
        $this->assertEquals('Cordelia Coordinator', $response->json('data.coordinators.0.name'));
        $this->assertArrayHasKey('avatar_url', $response->json('data.coordinators.0'));
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

        $response = $this->viewer($host, $network)->assertSuccessful();

        $nearby = $response->json('data.nearby_groups');
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

        $nearby = $this->viewer($host, $network)->assertSuccessful()->json('data.nearby_groups');

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

        $this->viewer($host, $network)->assertSuccessful()->assertJsonPath('data.nearby_groups', null);

        // A coordinator with no group of their own gets no nearby groups either.
        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);
        $this->viewer($coordinator, $network)->assertSuccessful()->assertJsonPath('data.nearby_groups', null);
    }
}
