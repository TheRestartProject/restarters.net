<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\Role;
use App\User;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserAdminTest extends TestCase
{
    public function provider()
    {
        return [
            [
                'Administrator', TRUE
            ],
            [
                'NetworkCoordinator', FALSE
            ],
            [
                'Host', FALSE
            ],
            [
                'Restarter', FALSE
            ],
        ];
    }

    /**
     *@dataProvider provider
     */
    public function testUsersPage($role, $cansee) {
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
