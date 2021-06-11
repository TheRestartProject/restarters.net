<?php

namespace Tests\Feature;

use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class AccountCreationTests extends TestCase {
    public function testRegister() {
        $userAttributes = $this->userAttributes();
        $response = $this->post('/user/register/',  $userAttributes);

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
        $this->assertDatabaseHas('users', [
            'email' => $userAttributes['email']
        ]);
    }

    public function testRegisterAgain() {
        $response = $this->post('/user/register/',  $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');

        $response = $this->post('/user/register/',  $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('');
    }

    public function testLogout() {
        $response = $this->post('/user/register/',  $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');

        $response = $this->get('/logout');

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    public function testLogoutAndBackIn() {
        $userAttributes = $this->userAttributes();
        $response = $this->post('/user/register/',  $userAttributes);

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');

        $response = $this->get('/logout');

        $response->assertStatus(302);
        $response->assertRedirect('login');

        $response = $this->post('/login',  [
            'email' => $userAttributes['email'],
            'password' => $userAttributes['password'],
            'my_time' => time()
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
    }
}
