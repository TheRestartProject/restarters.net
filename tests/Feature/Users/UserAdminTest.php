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
    public function testUsersPage() {
        // Fetch the list of all users and check that we're in it.
        $admin = factory(User::class)->states('Administrator')->create();
        $this->actingAs($admin);
        $response = $this->get('/user/all');
        $response->assertSee('Create new user');
        $response->assertSee($admin->name);
    }
}
