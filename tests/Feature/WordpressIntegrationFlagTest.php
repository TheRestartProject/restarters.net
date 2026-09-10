<?php

namespace Tests\Feature;

use App\Group;
use App\Network;
use App\Party;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Mockery;
use Tests\TestCase;

/**
 * The wordpress_integration feature flag exists so preview/staging
 * environments can never post to the live WordPress site, even if the
 * WP_XMLRPC_* secrets are accidentally present.
 */
class WordpressIntegrationFlagTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $queueManager = $this->app['queue'];
        $queueManager->setDefaultDriver('sync');
    }

    private function createEventInPushNetwork(): Party
    {
        $network = Network::factory()->create([
            'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create([
            'approved' => true,
        ]);
        $network->addGroup($group);

        return Party::factory()->create([
            'group' => $group->idgroups,
            'latitude' => 1,
            'longitude' => 1,
            'event_start_utc' => '2100-01-01T10:15:05+05:00',
            'event_end_utc' => '2100-01-01T13:45:05+05:00',
        ]);
    }

    /** @test */
    public function event_approval_does_not_push_when_integration_disabled(): void
    {
        config(['restarters.features.wordpress_integration' => false]);

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldNotReceive('newPost');
        }));

        $event = $this->createEventInPushNetwork();

        $this->assertFalse($event->shouldPushToWordpress());

        $event->approve();
        $this->artisan('queue:work --stop-when-empty');
    }

    /** @test */
    public function event_approval_pushes_when_integration_enabled(): void
    {
        config(['restarters.features.wordpress_integration' => true]);

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('newPost')->once();
        }));

        $event = $this->createEventInPushNetwork();

        $this->assertTrue($event->shouldPushToWordpress());

        $event->approve();
        $this->artisan('queue:work --stop-when-empty');
    }

    /** @test */
    public function manual_wordpress_commands_refuse_when_integration_disabled(): void
    {
        config(['restarters.features.wordpress_integration' => false]);

        $event = $this->createEventInPushNetwork();

        $this->artisan('wordpress:event:create ' . $event->idevents)
            ->expectsOutputToContain('disabled')
            ->assertExitCode(0);

        $this->artisan('wordpress:group:create ' . $event->theGroup->idgroups)
            ->expectsOutputToContain('disabled')
            ->assertExitCode(0);
    }

    /** @test */
    public function sync_discourseusernames_skips_when_integration_disabled(): void
    {
        config(['restarters.features.discourse_integration' => false]);

        $this->artisan('sync:discourseusernames')
            ->expectsOutputToContain('disabled')
            ->assertExitCode(0);
    }
}
