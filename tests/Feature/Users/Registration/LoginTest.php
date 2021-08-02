<?php

namespace Tests\Feature;

use App\Session;
use App\User;
use DB;
use Hash;
use Mockery;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function testLogin()
    {
        $restarter = factory(User::class)->state('Restarter')->create([
            'password' => Hash::make('passw0rd'),
                                                                      ]);

        // We've seen a Sentry problem which I can only see happening if there was invalid data in the cache.
        // Force that to happen to trigger code in loginRegisterStats which is resilient to it.
        \Cache::put('all_stats', [
            'device_count_status' => null,
        ], 120);

        $response = $this->get('/login');
        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $tokens = $crawler->filter('input[name=_token]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $tokenValue = $tokens[0]->attr('value');

        $names = $crawler->filter('input[name=my_name]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $nameValue = $names[0]->attr('value');

        $times = $crawler->filter('input[name=my_time]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $timeValue = $times[0]->attr('value');

        $response = $this->post('/login', [
            '_token' => $tokenValue,
            'my_name' => $nameValue,
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
