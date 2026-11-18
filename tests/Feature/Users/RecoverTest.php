<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * GET /user/recover is now a thin redirector into the SPA (F2-5) - submission is owned end-to-end
 * by POST /api/v2/auth/password/forgot, covered by
 * tests/Feature/Auth/AuthEndpointsTest.php::testForgotPasswordSendsRecoveryEmail. This only needs
 * to check the redirect itself; see PasswordResetTest for the paired /user/reset redirector.
 */
class RecoverTest extends TestCase
{
    public function testRecoverRedirectsToFrontend(): void
    {
        $frontend = rtrim(config('restarters.frontend_url'), '/');

        $response = $this->get('/user/recover');

        $response->assertRedirect($frontend.'/user/recover');
    }

    public function testPostRouteWasRemoved(): void
    {
        // Base TestCase disables exception handling; without this the 405
        // surfaces as MethodNotAllowedHttpException instead of a response.
        $this->withExceptionHandling();

        // The Blade form submission handler is gone - the API endpoint owns it now.
        $response = $this->post('/user/recover', [
            'email' => 'someone@example.com',
        ]);

        $response->assertStatus(405);
    }
}
