<?php

namespace Tests\Feature\Networks;

use App\Network;
use App\User;
use Tests\TestCase;

class NetworkReportingLinkTest extends TestCase
{
    private const REPORTS = 'https://reports.example.org/dashboard/7';

    private function networkUrl(Network $network, string $suffix = ''): string
    {
        return '/networks/' . $network->id . $suffix;
    }

    private function networkData($response): array
    {
        $props = $this->getVueProperties($response);

        foreach ($props as $prop) {
            if (array_key_exists(':network', $prop)) {
                return json_decode($prop[':network'], true);
            }
        }

        $this->fail('No :network property on the page');
    }

    public function testNetworkPageCarriesTheReportingUrl(): void
    {
        $network = Network::factory()->create([
            'reporting_url' => self::REPORTS,
        ]);

        $this->actingAs(User::factory()->administrator()->create());

        $data = $this->networkData($this->get($this->networkUrl($network)));
        $this->assertEquals(self::REPORTS, $data['reporting_url']);
    }

    public function testNetworkWithoutReportsHasNoReportingUrl(): void
    {
        $network = Network::factory()->create();

        $this->actingAs(User::factory()->administrator()->create());

        $data = $this->networkData($this->get($this->networkUrl($network)));
        $this->assertNull($data['reporting_url']);
    }

    public function testAdministratorCanSetTheReportingUrl(): void
    {
        $network = Network::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());

        $this->get($this->networkUrl($network, '/edit'))->assertSee('reporting_url');

        $this->put($this->networkUrl($network), [
            'reporting_url' => 'https://reports.example.org/dashboard/9',
        ])->assertRedirect();

        $this->assertEquals('https://reports.example.org/dashboard/9', $network->fresh()->reporting_url);

        // Blank clears it.
        $this->put($this->networkUrl($network), [
            'reporting_url' => '',
        ])->assertRedirect();

        $this->assertNull($network->fresh()->reporting_url);
    }

    public function testReportingUrlMustBeAWebAddress(): void
    {
        $network = Network::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());
        $this->withExceptionHandling();

        $this->put($this->networkUrl($network), [
            'reporting_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('reporting_url');

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

        $this->get($this->networkUrl($network, '/edit'))->assertDontSee('reporting_url');

        $this->put($this->networkUrl($network), [
            'reporting_url' => 'https://elsewhere.example.org/',
        ])->assertRedirect();

        $this->assertEquals(self::REPORTS, $network->fresh()->reporting_url);
    }
}
