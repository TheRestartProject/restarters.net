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
    public function testUsersPage($role, $cansee)
    {
        // Fetch the list of all users and check that we're in it.
        $admin = factory(User::class)->states($role)->create();
        $this->actingAs($admin);

        $response = $this->get('/user/all');

        if ($cansee) {
            $response->assertSee('Create new user');
            $response->assertSee($admin->name);
        } else {
            $response->assertDontSee('Create new user');
        }
    }
}
