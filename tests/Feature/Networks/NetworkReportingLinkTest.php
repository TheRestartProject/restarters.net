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
        $this->actingAs(User::factory()->administrator()->create());

        $this->patchJson($this->networkUrl($network), [
            'reporting_url' => 'https://reports.example.org/dashboard/9',
        ])->assertSuccessful()->assertJsonPath('data.reporting_url', 'https://reports.example.org/dashboard/9');

        $this->assertEquals('https://reports.example.org/dashboard/9', $network->fresh()->reporting_url);

        // Blank clears it.
        $this->patchJson($this->networkUrl($network), [
            'reporting_url' => '',
        ])->assertSuccessful();

        $this->assertNull($network->fresh()->reporting_url);
    }

    public function testReportingUrlMustBeAWebAddress(): void
    {
        $network = Network::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());

        $this->patchJson($this->networkUrl($network), [
            'reporting_url' => 'javascript:alert(1)',
        ])->assertStatus(422)->assertJsonValidationErrors('reporting_url');

        $this->assertNull($network->fresh()->reporting_url);
    }

    public function testCoordinatorCannotChangeTheReportingUrl(): void
    {
        $network = Network::factory()->create([
            'reporting_url' => self::REPORTS,
        ]);

        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);

        $this->patchJson($this->networkUrl($network), [
            'reporting_url' => 'https://elsewhere.example.org/',
        ])->assertForbidden();

        $this->assertEquals(self::REPORTS, $network->fresh()->reporting_url);
    }

    public function testAnonymousCannotChangeTheReportingUrl(): void
    {
        $network = Network::factory()->create(['reporting_url' => self::REPORTS]);

        $this->patchJson($this->networkUrl($network), ['reporting_url' => 'https://elsewhere.example.org/'])
            ->assertUnauthorized();

        $this->assertEquals(self::REPORTS, $network->fresh()->reporting_url);
    }
}
