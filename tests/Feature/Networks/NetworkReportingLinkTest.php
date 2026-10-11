<?php

namespace Tests\Feature\Networks;

use App\Network;
use App\User;
use Tests\TestCase;

class NetworkReportingLinkTest extends TestCase
{
    private const REPORTS = 'https://reports.example.org/dashboard/7';

    private function networkUrl(Network $network): string
    {
        return '/api/v2/networks/' . $network->id;
    }

    private function patchAs(?User $user, Network $network, array $data)
    {
        $url = $this->networkUrl($network);

        if ($user) {
            $this->actingAs($user);
            $url .= '?api_token=' . $user->api_token;
        }

        return $this->patchJson($url, $data);
    }

    public function testNetworkCarriesTheReportingUrl(): void
    {
        $network = Network::factory()->create([
            'reporting_url' => self::REPORTS,
        ]);

        $this->actingAs(User::factory()->administrator()->create());

        $this->get($this->networkUrl($network))->assertJsonPath('data.reporting_url', self::REPORTS);
    }

    public function testNetworkWithoutReportsHasNoReportingUrl(): void
    {
        $network = Network::factory()->create();

        $this->actingAs(User::factory()->administrator()->create());

        $this->get($this->networkUrl($network))->assertJsonPath('data.reporting_url', null);
    }

    public function testAdministratorCanSetTheReportingUrl(): void
    {
        $network = Network::factory()->create();
        $admin = User::factory()->administrator()->create(['api_token' => 'nrl-admin']);

        $this->patchAs($admin, $network, [
            'reporting_url' => 'https://reports.example.org/dashboard/9',
        ])->assertSuccessful()->assertJsonPath('data.reporting_url', 'https://reports.example.org/dashboard/9');

        $this->assertEquals('https://reports.example.org/dashboard/9', $network->fresh()->reporting_url);

        // Blank clears it.
        $this->patchAs($admin, $network, [
            'reporting_url' => '',
        ])->assertSuccessful();

        $this->assertNull($network->fresh()->reporting_url);
    }

    public function testReportingUrlMustBeAWebAddress(): void
    {
        $network = Network::factory()->create();
        $admin = User::factory()->administrator()->create(['api_token' => 'nrl-admin']);

        $this->patchAs($admin, $network, [
            'reporting_url' => 'javascript:alert(1)',
        ])->assertStatus(422)->assertJsonValidationErrors('reporting_url');

        $this->assertNull($network->fresh()->reporting_url);
    }

    public function testCoordinatorCannotChangeTheReportingUrl(): void
    {
        $network = Network::factory()->create([
            'reporting_url' => self::REPORTS,
        ]);

        $coordinator = User::factory()->networkCoordinator()->create(['api_token' => 'nrl-coord']);
        $network->addCoordinator($coordinator);

        $this->patchAs($coordinator, $network, [
            'reporting_url' => 'https://elsewhere.example.org/',
        ])->assertForbidden();

        $this->assertEquals(self::REPORTS, $network->fresh()->reporting_url);
    }

    public function testAnonymousCannotChangeTheReportingUrl(): void
    {
        $network = Network::factory()->create(['reporting_url' => self::REPORTS]);

        $this->patchAs(null, $network, ['reporting_url' => 'https://elsewhere.example.org/'])
            ->assertUnauthorized();

        $this->assertEquals(self::REPORTS, $network->fresh()->reporting_url);
    }
}
