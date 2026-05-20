<?php

namespace Tests\Feature\Helpers;

use App\Helpers\Fixometer;
use App\Jobs\RefreshLoginStats;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LoginRegisterStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    private function validStats(): array
    {
        return [
            'partiesCount' => 10,
            'waste_stats' => [['powered_footprint' => 1.0, 'unpowered_footprint' => 0.5, 'powered_waste' => 2.0, 'unpowered_waste' => 1.0]],
            'device_count_status' => [['status' => \App\Device::REPAIR_STATUS_FIXED, 'counter' => 42]],
        ];
    }

    public function testReturnsCachedStatsWithoutHittingDb(): void
    {
        Cache::put('all_stats', $this->validStats(), 86400);
        Cache::put('all_stats_fresh', true, 7200);

        // Should not dispatch a job or touch the DB
        Queue::fake();
        $stats = Fixometer::loginRegisterStats();

        $this->assertEquals(10, $stats['partiesCount']);
        $this->assertEquals(42, $stats['deviceCount']);
        $this->assertEquals(1.5, $stats['co2Total']);
        Queue::assertNothingPushed();
    }

    public function testDispatchesExactlyOneRefreshJobWhenStale(): void
    {
        Queue::fake();

        // Stats present but freshness marker absent = stale
        Cache::put('all_stats', $this->validStats(), 86400);

        $stats = Fixometer::loginRegisterStats();

        $this->assertEquals(10, $stats['partiesCount']);
        Queue::assertPushed(RefreshLoginStats::class, 1);
    }

    public function testDoesNotDispatchSecondJobWhenRefreshAlreadyQueued(): void
    {
        Queue::fake();

        Cache::put('all_stats', $this->validStats(), 86400);

        // Simulate two concurrent requests hitting stale cache
        Fixometer::loginRegisterStats();
        Fixometer::loginRegisterStats();

        // Lock prevents a second dispatch
        Queue::assertPushed(RefreshLoginStats::class, 1);
    }

    public function testRefreshJobUpdatesCache(): void
    {
        // Run the job and verify it populates the cache
        (new RefreshLoginStats())->handle();

        $stats = Cache::get('all_stats');
        $this->assertNotNull($stats);
        $this->assertArrayHasKey('partiesCount', $stats);
        $this->assertArrayHasKey('waste_stats', $stats);
        $this->assertArrayHasKey('device_count_status', $stats);
        $this->assertTrue(Cache::has('all_stats_fresh'));
    }
}
