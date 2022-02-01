<?php

namespace Tests\Feature;

use App\Notifications\ResetPassword;
use App\User;
use DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function testInvalidEmail() {
        $response = $this->post('/user/recover', [
            'email' => 'bademail!'
        ]);

        $response->assertSeeText(__('passwords.invalid'));
    }

    public function testUnknownEmail() {
        $response = $this->post('/user/recover', [
            'email' => 'nobody@nowhere.com'
        ]);

        $response->assertSeeText(__('passwords.user'));
    }

    public function testResetSuccess()
    {
        Notification::fake();
        Event::fake();

        $restarter = factory(User::class)->states('Restarter')->create();

        $response = $this->post('/user/recover', [
            'email' => $restarter->email
        ]);

        $response->assertSeeText(__('passwords.sent'));

        Notification::assertSentTo(
            [$restarter],
            ResetPassword::class,
            function ($notification, $channels, $user) {
                $mailData = $notification->toMail($user)->toArray();
                self::assertEquals(__('notifications.password_reset_subject', [], $user->language), $mailData['subject']);
                return true;
            }
        );

        $restarter->refresh();

        // Invalid code
        $response = $this->post('/user/reset', [
            'recovery' => '',
            'password' => 'newpass',
            'confirm_password' => 'newpass'
        ]);

        // Invalid code
        $response = $this->post('/user/reset', [
            'recovery' => $restarter->recovery . '1',
            'password' => 'newpass',
            'confirm_password' => 'newpass'
        ]);

        $response->assertSeeText('using is invalid');

        // Valid but mismatch passwords.
        $response = $this->post('/user/reset', [
            'recovery' => $restarter->recovery,
            'password' => 'newpass',
            'confirm_password' => 'mismatch'
        ]);

        $response->assertSeeText(__('passwords.match'));

        // Valid - should redirect to login patch and dispatch password changed event to update the wiki.
        $this->followingRedirects();
        $response = $this->post('/user/reset', [
            'recovery' => $restarter->recovery,
            'password' => 'newpass',
            'confirm_password' => 'newpass'
        ]);

        $response->assertSeeText(__('passwords.updated'));
        Event::assertDispatched(\App\Events\PasswordChanged::class);
    }
}
