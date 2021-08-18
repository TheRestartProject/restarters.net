<?php

namespace Tests\Feature;

use App\Notifications\ResetPassword;
use App\Events\PasswordChanged;
use App\User;
use DB;
use Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;
use Mockery;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class RecoverTest extends TestCase
{
    public function testRecover()
    {
        $restarter = factory(User::class)->state('Restarter')->create([
                                                                          'password' => Hash::make('passw0rd'),
                                                                      ]);

        // Fetch the recover page.
        $response = $this->get('/user/recover');
        $response->assertStatus(200);
        $response->assertSee(__('auth.forgotten_pw_text'));

        // Post a recover request - invalid email
        $response = $this->post('/user/recover', [
            'email' => 'zzzz'
        ]);
        $response->assertSee('alert-danger');

        // Post a valid recover request.
        Notification::fake();

        $response = $this->post('/user/recover', [
            'email' => $restarter->email
        ]);
        $response->assertSee('alert-success');

        $this->recovery = null;

        Notification::assertSentTo(
            $restarter,
            ResetPassword::class,
            function ($notification, $channels) use ($restarter)
            {
                // retrive the mail content
                $mailData = $notification->toMail($restarter)->toArray();
                $this->recovery = $mailData['actionUrl'];
                return true;
            });

        // Now fetch the reset page - first in error.
        $response = $this->get('/user/reset');
        $response->assertSee('The recovery code you\'re using is invalid');
        $response = $this->get($this->recovery . 'zz');
        $response->assertSee('The recovery code you\'re using is invalid');

        $response = $this->get($this->recovery);
        $response->assertSee('id="confirm_password"');

        // Now submit the reset request - first in error.
        $response = $this->post($this->recovery, [
            'password' => "1234",
            'confirm_password' => "1234",
            'recovery' => null
        ]);
        $response->assertSee('Recovery code invalid');

        $response = $this->post($this->recovery, [
            'password' => "1234",
            'confirm_password' => "12345",
            'recovery' => $this->recovery
        ]);
        $response->assertSee('The passwords do not match');

        $response = $this->post($this->recovery, [
            'password' => "1234",
            'confirm_password' => "1234",
            'recovery' => 'wibble'
        ]);
        $response->assertSee('No account matches the recovery code');

        // Now a successful reset, which redirects back to the login page.
        $p = strrpos($this->recovery, '=');
        $value = substr($this->recovery, $p + 1);

        Event::fake([
                        PasswordChanged::class,
                    ]);

        $response = $this->post($this->recovery, [
            'password' => "1234",
            'confirm_password' => "1234",
            'recovery' => $value
        ]);
        $this->assertTrue($response->isRedirection());
        Event::assertDispatched(PasswordChanged::class);

        $response->assertSessionHas('success');

        // Now log in with the new password.
        $response = $this->get('/login');
        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $tokens = $crawler->filter('input[name=_token]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $tokenValue = $tokens[0]->attr('value');

        $names = $crawler->filter('input[name=my_name]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $nameValue = $names[0]->attr('value');

        $times = $crawler->filter('input[name=my_time]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $timeValue = $times[0]->attr('value');

        $response = $this->post('/login', [
            '_token' => $tokenValue,
            'my_name' => $nameValue,
            'my_time' => $timeValue,
            'email' => $restarter->email,
            'password' => '1234',
        ]);

        // Should redirect to dashboard.
        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/dashboard'));
    }
}
