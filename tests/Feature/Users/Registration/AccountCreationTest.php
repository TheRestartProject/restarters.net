<?php

namespace Tests\Feature;

use App\Role;
use App\User;
use DB;
use Hash;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class AccountCreationTest extends TestCase
{
    public function testRegister()
    {
        $response = $this->get('/user/register');
        $response->assertSee(__('registration.reg-step-1-heading'), $response->getContent());

        $userAttributes = $this->userAttributes();
        $response = $this->post('/user/register/', $userAttributes);

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
        $this->assertDatabaseHas('users', [
            'email' => $userAttributes['email'],
        ]);

        $user = User::latest()->first();
        $this->assertEquals(51.507, round($user->latitude, 3));
        $this->assertEquals(-0.128, round($user->longitude, 3));
    }

    public function testWorkbenchThenRegister() {
        $this->get('/workbench');
        $userAttributes = $this->userAttributes();
        $response = $this->post('/user/register/', $userAttributes);

        $response->assertStatus(302);
        $response->assertRedirect('workbench');
    }

    public function testRegisterInvalidAddress()
    {
        $userAttributes = $this->userAttributes();

        // Specify an invalid city and force geocoding to fail by invalidating the Google key.
        $good = Config::get(['GOOGLE_API_CONSOLE_KEY']);
        Config::set(['GOOGLE_API_CONSOLE_KEY' => 'zzz']);

        $userAttributes['city'] = 'zzzzzzz';
        $response = $this->post('/user/register/', $userAttributes);

        Config::set(['GOOGLE_API_CONSOLE_KEY' => $good]);

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
        $this->assertDatabaseHas('users', [
            'email' => $userAttributes['email'],
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    public function testRegisterAgain()
    {
        $response = $this->post('/user/register/', $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');

        $response = $this->post('/user/register/', $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('');
    }

    public function testLogout()
    {
        $response = $this->post('/user/register/', $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');

        $response = $this->get('/logout');

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    public function testLogoutAndBackIn()
    {
        $userAttributes = $this->userAttributes();
        $response = $this->post('/user/register/', $userAttributes);

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');

        $response = $this->get('/logout');

        $response->assertStatus(302);
        $response->assertRedirect('login');

        $response = $this->post('/login', [
            'email' => $userAttributes['email'],
            'password' => $userAttributes['password'],
            'my_time' => time(),
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
    }

    public function testValidEmail() {

        // Check with a registered email.
        $restarter = factory(User::class)->state('Restarter')->create();
        $response = $this->post('user/register/check-valid-email', [
            'email' => $restarter->email
        ]);

        $this->assertEquals([
                                'message' => __('auth.email_address_validation'),
                            ], json_decode($response->getContent(), true));

        $response = $this->post('user/register/check-valid-email', [
            'email' => 'test@invalid.com'
        ]);

        $this->assertNull(json_decode($response->getContent(), true));
    }

    public function testAdminCreate() {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $userAttributes = $this->userAttributes();
        $response = $this->post('/user/create', [
            'name' => $userAttributes['name'],
            'email' => $userAttributes['email'],
            'role' => Role::RESTARTER,
        ]);

        $response->assertSee('User created correctly');
        $this->assertDatabaseHas('users', [
            'email' => $userAttributes['email'],
        ]);
    }
}
