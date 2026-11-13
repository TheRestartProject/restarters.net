<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * GET /user/reset is now a thin redirector into the SPA (F2-5) - submission is owned end-to-end
 * by POST /api/v2/auth/password/reset, covered by
 * tests/Feature/Auth/AuthEndpointsTest.php::testResetPasswordWithValidToken and
 * ::testResetPasswordRejectsExpiredOrUnknownToken. This only needs to check the redirect itself;
 * the emailed link (App\Http\Controllers\API\AuthController::forgotPasswordv2) still points here
 * unchanged, carrying ?recovery=.
 */
class PasswordResetTest extends TestCase
{
    public function testResetCarriesRecoveryCodeToFrontend(): void
    {
        $frontend = rtrim(config('restarters.frontend_url'), '/');

        $response = $this->get('/user/reset?recovery=abc123');

        $response->assertRedirect($frontend.'/user/reset?recovery=abc123');
    }

    public function testResetWithNoRecoveryCodeStillRedirects(): void
    {
        $frontend = rtrim(config('restarters.frontend_url'), '/');

        $response = $this->get('/user/reset');

        $response->assertRedirect($frontend.'/user/reset');
    }

    public function testPostRouteWasRemoved(): void
    {
        // Base TestCase disables exception handling; without this the 405
        // surfaces as MethodNotAllowedHttpException instead of a response.
        $this->withExceptionHandling();

        // The Blade form submission handler is gone - the API endpoint owns it now.
        $response = $this->post('/user/reset', [
            'recovery' => 'abc123',
            'password' => 'newpass',
            'confirm_password' => 'newpass',
        ]);

        $response->assertStatus(405);
    }
}
