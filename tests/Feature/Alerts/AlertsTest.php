<?php

namespace Tests\Feature\Alerts;

use App\Alerts;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\Role;
use Cache;
use DB;
use Hash;
use Tests\ApiTestCase;
use Illuminate\Auth\AuthenticationException;

class AlertsTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure there are no alerts from previous tests
        DB::table('alerts')->truncate();
        Cache::clear('alerts');
    }

    public function testListNonePresent(): void
    {
        // List - no alerts present.
        $response = $this->get('/api/v2/alerts');
        $response->assertSuccessful();

        $json = json_decode($response->getContent(), true);
        self::assertEquals(0, count($json['data']));
    }

    #[DataProvider('roleProvider')]
    public function testCreate($role, $allowed): void {
        $user = null;
        $tokenstr = null;

        // Use faster login method to avoid full HTTP registration flow
        $user = $this->fastLoginAsTestUser($role);

        if ($role != Role::GUSET) {
            $token = $user->api_token;
            $tokenstr = "?api_token=$token";
        }

        if (!$allowed) {
            $this->expectException(AuthenticationException::class);
        }

        $response = $this->put('/api/v2/alerts' . $tokenstr, [
            'title' => 'Test alert',
            'html' => '<p>Test alert</p>',
            'start' => '2001-01-01T00:00:00Z',
            'end' => '2038-01-01T02:00:00Z',
        ]);

        if ($allowed) {
            $response->assertSuccessful();
            $json = json_decode($response->getContent(), true);
            $id = $json['id'];
            self::assertNotNull($id);

            // Should be able to see it in the list.
            $response = $this->get('/api/v2/alerts' . $tokenstr);
            $response->assertSuccessful();

            $json = json_decode($response->getContent(), true);
            self::assertEquals(1, count($json['data']));
            self::assertEquals($id, $json['data'][0]['id']);

            // Should be able to edit it.
            $response = $this->patch("/api/v2/alerts/$id" . $tokenstr, [
                'title' => 'Test alert2',
                'html' => '<p>Test alert2</p>',
                'start' => '2001-01-02T00:00:00Z',
                'end' => '2038-01-02T02:00:00Z',
            ]);

            $response = $this->get('/api/v2/alerts' . $tokenstr);
            $response->assertSuccessful();

            $json = json_decode($response->getContent(), true);
            self::assertEquals(1, count($json['data']));
            self::assertEquals($id, $json['data'][0]['id']);
            self::assertEquals('Test alert2', $json['data'][0]['title']);
            self::assertEquals('<p>Test alert2</p>', $json['data'][0]['html']);
            self::assertEquals('2001-01-02T00:00:00+00:00', $json['data'][0]['start']);
            self::assertEquals('2038-01-02T02:00:00+00:00', $json['data'][0]['end']);
        }
    }

    public static function roleProvider(): array {
        return [
            [ Role::GUSET, FALSE ],
            [ Role::RESTARTER, FALSE ],
            [ Role::ADMINISTRATOR, TRUE ],
            [ Role::NETWORK_COORDINATOR, FALSE ],
            [ Role::HOST, FALSE ],
        ];
    }

    public function testArtisan(): void {
        $this->artisan('alert:create', [
            'title' => 'Test alert',
            'html' => '<p>Test alert</p>',
            'start' => '-3 hour',
            'end' => 'tomorrow',
        ])->assertExitCode(0);

        $response = $this->get('/api/v2/alerts');
        $response->assertSuccessful();

        $json = json_decode($response->getContent(), true);
        self::assertEquals(1, count($json['data']));
        self::assertEquals('Test alert', $json['data'][0]['title']);
        self::assertEquals('<p>Test alert</p>', $json['data'][0]['html']);
    }
}
