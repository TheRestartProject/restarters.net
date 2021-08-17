<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\User;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MenusTest extends TestCase
{
    public function provider()
    {
        return [
            [
                'Administrator',
                [
                    0 => 'Administrator',
                    1 => 'Reporting',
                    2 => 'General',
                ],
            ],
            [
                'NetworkCoordinator',
                [
                    0 => 'Administrator',
                    1 => 'General',
                ],
            ],
            [
                'Host',
                [
                    0 => 'Reporting',
                    1 => 'General',
                ],
            ],
            [
                'Restarter',
                [
                    0 => 'General',
                ],
            ],
        ];
    }

    /**
     *@dataProvider provider
     */
    public function testSections($role, $present)
    {
        $user = factory(User::class)->states($role)->create();
        $this->actingAs($user);

        $response = $this->get('/user/menus');
        $menus = json_decode($response->getContent(), true);

        $this->assertEquals($present, array_keys($menus));
    }

    public function testLoggedOut() {
        $this->expectException(NotFoundHttpException::class);
        $this->get('/user/menus');
    }
}
