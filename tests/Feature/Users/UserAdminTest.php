<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    public function provider()
    {
        return [
            [
                'Administrator', true,
            ],
            [
                'NetworkCoordinator', false,
            ],
            [
                'Host', false,
            ],
            [
                'Restarter', false,
            ],
        ];
    }

    /**
     *@dataProvider provider
     */
    public function testUsersPage($role, $cansee): void
    {
        // Fetch the list of all users and check that we're in it.
        $admin = User::factory()->{lcfirst($role)}()->create();
        $this->actingAs($admin);

        $response = $this->get('/user/all');

        if ($cansee) {
            $response->assertSee('Create new user');
            $response->assertSee($admin->name);
        } else {
            $response->assertDontSee('Create new user');
        }
    }

    public function testSoftDelete(): void {
        $user = User::factory()->restarter()->create();
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->post('/user/soft-delete', [
            'id' => $user->id
        ]);
        $response->assertSessionHas('danger');
        $this->assertTrue($response->isRedirection());

        $response = $this->post('/user/soft-delete');
        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/login'));
    }
}
