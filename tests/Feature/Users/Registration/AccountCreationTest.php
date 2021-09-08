<?php

namespace Tests\Feature;

use App\Role;
use App\User;
use DB;
use Hash;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

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
        $this->assertEquals(51.5073509, $user->latitude);
        $this->assertEquals(-0.1277583, $user->longitude);
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

        // Specify an invalid city
        $userAttributes['city'] = 'zzzzzzz';
        $response = $this->post('/user/register/', $userAttributes);

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
