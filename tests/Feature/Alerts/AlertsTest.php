<?php

namespace Tests\Feature\Alerts;

use App\Alerts;
use App\Role;
use Cache;
use App\User;
use DB;
use Hash;
use Tests\TestCase;
use Illuminate\Auth\AuthenticationException;

class AlertsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear('alerts');
    }

    public function testListNonePresent()
    {
        // List - no alerts present.
        $response = $this->get('/api/v2/alerts');
        $response->assertSuccessful();

        $json = json_decode($response->getContent(), true);
        self::assertEquals(0, count($json['data']));
    }

    /**
     * @dataProvider roleProvider
     */
    public function testCreate($role, $allowed) {
        if ($role != Role::GUSET) {
            $this->loginAsTestUser($role);
        }

        if (!$allowed) {
            $this->expectException(AuthenticationException::class);
        }

        $response = $this->put('/api/v2/alerts', [
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
            $response = $this->get('/api/v2/alerts');
            $response->assertSuccessful();

            $json = json_decode($response->getContent(), true);
            self::assertEquals(1, count($json['data']));
            self::assertEquals($id, $json['data'][0]['id']);
        }
    }

    public function roleProvider() {
        return [
            [ Role::GUSET, FALSE ],
            [ Role::RESTARTER, FALSE ],
            [ Role::ADMINISTRATOR, TRUE ],
            [ Role::NETWORK_COORDINATOR, FALSE ],
            [ Role::HOST, FALSE ],
        ];
    }
}
