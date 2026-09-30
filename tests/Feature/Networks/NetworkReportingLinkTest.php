<?php

namespace Tests\Feature\Networks;

use App\Network;
use App\User;
use Tests\TestCase;

class NetworkReportingLinkTest extends TestCase
{
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
            'reporting_url' => 'https://reports.example.org/dashboard/7',
        ]);

        $this->actingAs(User::factory()->administrator()->create());

        $data = $this->networkData($this->get('/networks/' . $network->id));
        $this->assertEquals('https://reports.example.org/dashboard/7', $data['reporting_url']);
    }

    public function testNetworkWithoutReportsHasNoReportingUrl(): void
    {
        $network = Network::factory()->create();

        $this->actingAs(User::factory()->administrator()->create());

        $data = $this->networkData($this->get('/networks/' . $network->id));
        $this->assertNull($data['reporting_url']);
    }

    public function testAdministratorCanSetTheReportingUrl(): void
    {
        $network = Network::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());

        $this->get('/networks/' . $network->id . '/edit')->assertSee('reporting_url');

        $this->put('/networks/' . $network->id, [
            'reporting_url' => 'https://reports.example.org/dashboard/9',
        ])->assertRedirect();

        $this->assertEquals('https://reports.example.org/dashboard/9', $network->fresh()->reporting_url);

        // Blank clears it.
        $this->put('/networks/' . $network->id, [
            'reporting_url' => '',
        ])->assertRedirect();

        $this->assertNull($network->fresh()->reporting_url);
    }

    public function testReportingUrlMustBeAWebAddress(): void
    {
        $network = Network::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());
        $this->withExceptionHandling();

        $this->put('/networks/' . $network->id, [
            'reporting_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('reporting_url');

        $this->assertNull($network->fresh()->reporting_url);
    }

    public function testCoordinatorCannotChangeTheReportingUrl(): void
    {
        $network = Network::factory()->create([
            'reporting_url' => 'https://reports.example.org/dashboard/7',
        ]);

        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);

        $this->get('/networks/' . $network->id . '/edit')->assertDontSee('reporting_url');

        $this->put('/networks/' . $network->id, [
            'reporting_url' => 'https://elsewhere.example.org/',
        ])->assertRedirect();

        $this->assertEquals('https://reports.example.org/dashboard/7', $network->fresh()->reporting_url);
    }
}
