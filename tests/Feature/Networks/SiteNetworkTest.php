<?php

namespace Tests\Feature\Networks;

use App\Group;
use App\Network;
use App\User;
use Tests\TestCase;

/**
 * Network sites (repairtogether.restarters.net etc.): the Nuxt client is served
 * on the network's own host but calls the API on restarters.net, so the API
 * works out which site the browser is on from the Origin header, falling back
 * to the request's own host for same-origin requests.
 */
class SiteNetworkTest extends TestCase
{
    private Network $repairTogether;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repairTogether = Network::factory()->create([
            'name' => 'Repair Together',
            'shortname' => 'repairtogether',
            'default_language' => 'fr-BE',
        ]);
    }

    /**
     * @story:SessionController::getSessionv2
     */
    public function testSessionReportsTheNetworkOfTheSiteFromTheOrigin(): void
    {
        $response = $this->getJson('/api/v2/session', ['Origin' => 'https://repairtogether.restarters.net']);

        $response->assertOk();
        $this->assertEquals($this->repairTogether->id, $response->json('data.site.network_id'));
        $this->assertEquals('repairtogether', $response->json('data.site.shortname'));
        $this->assertEquals('fr-BE', $response->json('data.site.default_language'));
        $this->assertStringEndsWith('/images/logos/repairtogether.png', $response->json('data.site.logo'));
    }

    /**
     * @story:SessionController::getSessionv2
     */
    public function testSessionFallsBackToTheRequestHostWithoutAnOrigin(): void
    {
        $response = $this->getJson('http://repairtogether.restarters.net/api/v2/session');

        $response->assertOk();
        $this->assertEquals('repairtogether', $response->json('data.site.shortname'));
    }

    /**
     * @story:SessionController::getSessionv2
     */
    public function testMainSiteUsesTheRestartersWordmark(): void
    {
        $response = $this->getJson('/api/v2/session', ['Origin' => 'https://restarters.net']);

        $response->assertOk();
        $this->assertEquals('restarters', $response->json('data.site.shortname'));
        $this->assertNull($response->json('data.site.logo'));
    }

    /**
     * @dataProvider networkSites
     * @story:SessionController::getSessionv2
     */
    public function testEachNetworkSiteHasLiveLogo(string $host, string $shortname, string $logo): void
    {
        if ($shortname !== 'repairtogether') {
            Network::factory()->create(['shortname' => $shortname]);
        }

        $response = $this->getJson('/api/v2/session', ['Origin' => "https://$host"]);

        $this->assertEquals($shortname, $response->json('data.site.shortname'));
        $this->assertStringEndsWith("/images/logos/$logo", $response->json('data.site.logo'));
    }

    public static function networkSites(): array
    {
        return [
            ['repairshare.restarters.net', 'repairshare', 'repairshare.png'],
            ['repairtogether.restarters.net', 'repairtogether', 'repairtogether.png'],
            ['hauts-de-france.restarters.net', 'hauts-de-france', 'RC-Hauts-de-France.png'],
        ];
    }

    /**
     * Live records the site a logged-in user is on as their repair_network on
     * every page load; the client loads /api/v2/session on every app load and
     * after logging in, so that is where it is recorded now.  A group they then
     * create joins that network.
     *
     * @story:SessionController::getSessionv2
     * @story:GroupController::createGroupv2
     */
    public function testLoadingTheAppOnANetworkSiteRecordsItAsTheUsersNetwork(): void
    {
        $restarters = Network::where('shortname', 'restarters')->first();
        $user = User::factory()->administrator()->create(['repair_network' => $restarters->id]);
        $headers = [
            'Authorization' => 'Bearer '.$user->createToken('spa')->plainTextToken,
            'Origin' => 'https://repairtogether.restarters.net',
        ];

        $this->getJson('/api/v2/session', $headers)->assertOk();
        $this->assertEquals($this->repairTogether->id, $user->fresh()->repair_network);

        $this->postJson('/api/v2/groups', [
            'name' => 'Repair Café Namur',
            'location' => 'London',
            'description' => 'A repair group',
            'timezone' => 'Europe/Brussels',
        ], $headers)->assertSuccessful();

        $group = Group::where('name', 'Repair Café Namur')->first();
        $this->assertTrue($group->isMemberOf($this->repairTogether));
        $this->assertFalse($group->isMemberOf($restarters));
    }

    /**
     * @story:SessionController::getSessionv2
     */
    public function testLoadingTheMainSiteRecordsRestartersAsTheUsersNetwork(): void
    {
        $user = User::factory()->restarter()->create(['repair_network' => $this->repairTogether->id]);

        $this->getJson('/api/v2/session', [
            'Authorization' => 'Bearer '.$user->createToken('spa')->plainTextToken,
            'Origin' => 'https://restarters.net',
        ])->assertOk();

        $this->assertEquals(Network::where('shortname', 'restarters')->first()->id, $user->fresh()->repair_network);
    }
}
