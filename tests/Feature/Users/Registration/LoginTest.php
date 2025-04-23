<?php

namespace Tests\Feature;

use App\Models\Session;
use App\Models\User;
use DB;
use Hash;
use Mockery;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function testLogin(): void
    {
        $restarter = User::factory()->restarter()->create([
            'password' => Hash::make('passw0rd'),
                                                                      ]);

        // We've seen a Sentry problem which I can only see happening if there was invalid data in the cache.
        // Force that to happen to trigger code in loginRegisterStats which is resilient to it.
        \Cache::put('all_stats', [
            'device_count_status' => null,
        ], 120);

        $response = $this->get('/login');
        $response->assertStatus(200);

        // Check props on LoginPage vue element.
        $props = $this->assertVueProperties($response, [
            [
                ':error'=> "false",
                'email' => ""
            ],
        ]);

        $tokenValue = $props[0]['csrf'];
        $timeValue = $props[0]['time'];

        $response = $this->post('/login', [
            '_token' => $tokenValue,
            'my_name' => 'my_name',
            'my_time' => $timeValue,
            'email' => $restarter->email,
            'password' => 'passw0rd',
        ]);

        // Should redirect to dashboard.
        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/dashboard'));
    }
}
