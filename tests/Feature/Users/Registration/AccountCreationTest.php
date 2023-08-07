<?php

namespace Tests\Feature;

use App\Role;
use App\User;
use DB;
use Hash;
use Illuminate\Support\Facades\Config;
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
        $this->assertEquals(51.507, round($user->latitude, 3));
        $this->assertEquals(-0.128, round($user->longitude, 3));

        // JS would load this.
        $response = $this->get('/user/onboarding-complete');
        $response->assertStatus(200);
        $response->assertSee('true');

        // No notifications immediately after creation.
        $response2 = $this->get('/api/users/' . $user->id . '/notifications');
        $this->assertEquals([
                                'success' => 'success',
                                'restarters' => 0,
                                'discourse' => 0
                            ], json_decode($response2->getContent(), TRUE));
    }

    public function testWorkbenchThenRegister()
    {
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
        $good = Config::get('MAPBOX_TOKEN');
        Config::set('MAPBOX_TOKEN', 'zzz');

        $userAttributes['city'] = 'zzzzzzz';
        $response = $this->post('/user/register/', $userAttributes);

        Config::set('MAPBOX_TOKEN', $good);

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

        // Also require my_name when logged in.
        $response = $this->post('/user/register/', [
            'age'                 => 1950,
            'country'             => 'GB',
            'my_name'             => 'honeypot',
            'my_time'             => time(),
            'consent_gdpr'        => true,
            'consent_past_data'   => true,
            'consent_future_data' => true,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');

        \Auth::user()->refresh();
        $this->assertEquals(1950, \Auth::user()->age);
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

    public function testValidEmail()
    {

        // Check with a registered email.
        $restarter = User::factory()->restarter()->create();
        $response = $this->post('user/register/check-valid-email', [
            'email' => $restarter->email,
        ]);

        $this->assertEquals([
                                'message' => __('auth.email_address_validation'),
                            ], json_decode($response->getContent(), true));

        $response = $this->post('user/register/check-valid-email', [
            'email' => 'test@invalid.com',
        ]);

        $this->assertNull(json_decode($response->getContent(), true));
    }

    public function testAdminCreate()
    {
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

    public function testValidationFail()
    {
        $userAttributes = $this->userAttributes();
        unset($userAttributes['consent_gdpr']);
        $response = $this->post('/user/register/', $userAttributes);

        $response->assertStatus(302);
        $response->assertSessionHas('errors');
    }
}
