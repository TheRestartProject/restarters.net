<?php

namespace Tests\Feature\Events;

use App\ApiClient;
use App\Group;
use App\Network;
use App\Party;
use App\Role;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class PublicEventsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['restarters.features.public_events_api' => true]);
    }

    public function testPublicEventsApiRequiresBearerKey()
    {
        $response = $this->get('/api/public/v2/events');
        $response->assertStatus(401);
    }

    public function testPublicEventsApiIgnoresQueryTokenAuth()
    {
        $response = $this->get('/api/public/v2/events?api_token=not_a_valid_public_key');
        $response->assertStatus(401);
    }

    public function testPublicEventsApiListsOnlyUpcomingApprovedEventsByDefault()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $approvedGroupId = $this->createGroup('Public API Group', 'https://example.com', 'London', 'Some text', true, true);
        $futureApprovedId = $this->createEvent($approvedGroupId, 'tomorrow', true, true);
        $pastApprovedId = $this->createEvent($approvedGroupId, 'yesterday', true, true);
        $futureUnapprovedId = $this->createEvent($approvedGroupId, 'next week', true, false);

        $unapprovedGroupId = $this->createGroup('Hidden Group', 'https://example.com', 'London', 'Some text', true, false);
        $hiddenEventId = $this->createEvent($unapprovedGroupId, 'next week', true, true);

        $archivedGroupId = $this->createGroup('Archived Group', 'https://example.com', 'London', 'Some text', true, true);
        $archivedEventId = $this->createEvent($archivedGroupId, 'next week', true, true);
        $archivedGroup = Group::findOrFail($archivedGroupId);
        $archivedGroup->archived_at = '2022-01-01 00:00:00';
        $archivedGroup->save();

        $token = $this->createPublicApiToken();
        $response = $this->withHeader('Authorization', 'Bearer '.$token)->get('/api/public/v2/events');
        $response->assertSuccessful();

        $json = json_decode($response->getContent(), true);
        $ids = array_column($json['data'], 'id');

        $this->assertContains($futureApprovedId, $ids);
        $this->assertNotContains($pastApprovedId, $ids);
        $this->assertNotContains($futureUnapprovedId, $ids);
        $this->assertNotContains($hiddenEventId, $ids);
        $this->assertNotContains($archivedEventId, $ids);

        $this->assertArrayHasKey('meta', $json);
        $this->assertArrayHasKey('sync', $json);
        $this->assertArrayHasKey('generated_at', $json['sync']);
        $this->assertArrayHasKey('max_updated_at', $json['sync']);
        $this->assertArrayHasKey('description', $json['data'][0]);
        $this->assertArrayNotHasKey('stats', $json['data'][0]);
        $this->assertArrayNotHasKey('network_data', $json['data'][0]);
        $this->assertArrayNotHasKey('networks', $json['data'][0]['group']);
    }

    public function testPublicEventsApiSupportsGroupFilters()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $group1Id = $this->createGroup('Group One', 'https://example.com', 'London', 'Some text', true, true);
        $group2Id = $this->createGroup('Group Two', 'https://example.com', 'London', 'Some text', true, true);

        $event1 = $this->createEvent($group1Id, 'tomorrow', true, true);
        $event2 = $this->createEvent($group2Id, 'tomorrow', true, true);

        $token = $this->createPublicApiToken();

        $groupResponse = $this->withHeader('Authorization', 'Bearer '.$token)->get("/api/public/v2/groups/{$group1Id}/events");
        $groupResponse->assertSuccessful();
        $groupJson = json_decode($groupResponse->getContent(), true);
        $groupIds = array_column($groupJson['data'], 'id');
        $this->assertContains($event1, $groupIds);
        $this->assertNotContains($event2, $groupIds);
    }

    public function testPublicEventsApiRespectsAllowedNetworkRestrictions()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $group1Id = $this->createGroup('Restricted Group One', 'https://example.com', 'London', 'Some text', true, true);
        $group2Id = $this->createGroup('Restricted Group Two', 'https://example.com', 'London', 'Some text', true, true);

        $event1 = $this->createEvent($group1Id, 'tomorrow', true, true);
        $event2 = $this->createEvent($group2Id, 'tomorrow', true, true);

        $allowedNetwork = Network::factory()->create();
        $blockedNetwork = Network::factory()->create();
        $allowedNetwork->addGroup(Group::findOrFail($group1Id));
        $blockedNetwork->addGroup(Group::findOrFail($group2Id));

        $token = $this->createPublicApiToken([
            'allowed_network_ids' => [$allowedNetwork->id],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->get('/api/public/v2/events');
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $ids = array_column($json['data'], 'id');

        $this->assertContains($event1, $ids);
        $this->assertNotContains($event2, $ids);
    }

    public function testPublicEventsApiEnforcesAllowedOriginsWhenConfigured()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $groupId = $this->createGroup('Origin Test Group', 'https://example.com', 'London', 'Some text', true, true);
        $this->createEvent($groupId, 'tomorrow', true, true);

        $token = $this->createPublicApiToken([
            'allowed_origins' => ['https://allowed.example'],
        ]);

        $forbidden = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Origin' => 'https://disallowed.example',
        ])->get('/api/public/v2/events');
        $forbidden->assertStatus(403);

        $allowed = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Origin' => 'https://allowed.example',
        ])->get('/api/public/v2/events');
        $allowed->assertSuccessful();
    }

    public function testPublicEventsApiRejectsExpiredTokens()
    {
        $token = $this->createPublicApiToken([
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->get('/api/public/v2/events');
        $response->assertStatus(401);
    }

    public function testPublicEventsApiRejectsClientsWithoutRequiredScope()
    {
        $token = $this->createPublicApiToken([
            'scopes' => ['groups:read'],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->get('/api/public/v2/events');
        $response->assertStatus(403);
    }

    public function testPublicEventsApiAppliesPerClientRateLimits()
    {
        $client = ApiClient::factory()->create([
            'rate_limit_per_minute' => 2,
        ]);

        $makeRequest = function () use ($client) {
            $request = Request::create('/api/public/v2/events', 'GET', [], [], [], [
                'REMOTE_ADDR' => '127.0.0.1',
            ]);
            $request->attributes->set('apiClient', $client);

            return $request;
        };

        $rateLimiter = app(CacheRateLimiter::class);
        $limit = $rateLimiter->limiter('public-api')($makeRequest());
        $rateLimiter->clear(md5('public-api'.$limit->key));

        $middleware = new ThrottleRequests($rateLimiter);

        $first = $middleware->handle($makeRequest(), function () {
            return response()->json(['ok' => true]);
        }, 'public-api');

        $second = $middleware->handle($makeRequest(), function () {
            return response()->json(['ok' => true]);
        }, 'public-api');

        $this->assertEquals(200, $first->getStatusCode());
        $this->assertEquals(200, $second->getStatusCode());

        $this->expectException(ThrottleRequestsException::class);

        $middleware->handle($makeRequest(), function () {
            return response()->json(['ok' => true]);
        }, 'public-api');
    }

    public function testPublicEventsApiShowEventReturnsOnlyPublicApprovedEvents()
    {
        $this->withExceptionHandling();
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $groupId = $this->createGroup('Show Event Group', 'https://example.com', 'London', 'Some text', true, true);
        $approvedEventId = $this->createEvent($groupId, 'tomorrow', true, true);
        $unapprovedEventId = $this->createEvent($groupId, 'next week', true, false);

        $token = $this->createPublicApiToken();

        $visible = $this->withHeader('Authorization', 'Bearer '.$token)->get("/api/public/v2/events/{$approvedEventId}");
        $visible->assertSuccessful();
        $json = json_decode($visible->getContent(), true);
        $this->assertEquals($approvedEventId, $json['data']['id']);
        $this->assertArrayNotHasKey('stats', $json['data']);
        $this->assertArrayNotHasKey('network_data', $json['data']);
        $this->assertArrayNotHasKey('networks', $json['data']['group']);

        $hidden = $this->withHeader('Authorization', 'Bearer '.$token)->get("/api/public/v2/events/{$unapprovedEventId}");
        $hidden->assertStatus(404);
    }

    public function testPublicEventsApiSupportsUpdatedWindowFilters()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $groupId = $this->createGroup('Updated Window Group', 'https://example.com', 'London', 'Some text', true, true);
        $eventId = $this->createEvent($groupId, 'tomorrow', true, true);

        $event = Party::findOrFail($eventId);
        $event->timestamps = false;
        $event->updated_at = Carbon::parse('2000-01-01 00:00:00')->toDateTimeString();
        $event->save();

        $token = $this->createPublicApiToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->get(
            '/api/public/v2/events?updated_start='.urlencode(Carbon::parse('2010-01-01')->toIso8601String())
        );
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals([], $json['data']);
    }

    private function createPublicApiToken(array $attributes = []): string
    {
        $token = 'public_api_token_'.uniqid();

        ApiClient::factory()->create(array_merge([
            'token_hash' => hash('sha256', $token),
            'token_hint' => substr($token, 0, 6).'...'.substr($token, -4),
            'scopes' => ['events:read'],
            'active' => true,
            'expires_at' => null,
        ], $attributes));

        return $token;
    }
}
