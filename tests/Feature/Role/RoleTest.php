<?php

namespace Tests\Feature\Role;

use App\Providers\RouteServiceProvider;
use App\Role;
use Illuminate\Auth\AuthenticationException;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function testLoggedOut(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->get('/role');
    }

    public function testNotAdmin(): void
    {
        $this->loginAsTestUser(Role::RESTARTER);
        $response = $this->get('/role');
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function testRolesAdminPageRendersWithVueData(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->get('/role');
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('<RolesPage', $html);
        // Host role should be hydrated into :initial-roles
        $this->assertMatchesRegularExpression(
            '/:initial-roles="\[[^"]*&quot;name&quot;:&quot;Host&quot;[^"]*\]"/',
            $html,
            'Expected the Host role inside :initial-roles'
        );
        $this->assertStringContainsString(':initial-permissions=', $html);
        $this->assertStringContainsString(':initial-edit-id="null"', $html);
    }

    public function testLegacyEditUrlPreOpensEditModalForRole(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->get('/role/edit/' . Role::HOST);
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('<RolesPage', $html);
        $this->assertStringContainsString(':initial-edit-id="' . Role::HOST . '"', $html);
    }
}
