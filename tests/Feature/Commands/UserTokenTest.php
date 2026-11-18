<?php

namespace Tests\Commands;

use App\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * user:token issues a named Sanctum token, so an admin can hand an API key to
 * someone (a group's own tooling, a partner site) without the old
 * users.api_token column.
 */
class UserTokenTest extends TestCase
{
    public function testIssuesANamedTokenThatTheApiAccepts(): void
    {
        $user = User::factory()->restarter()->create(['email' => 'tokens@example.com']);

        $exit = Artisan::call('user:token', ['email' => 'tokens@example.com', 'name' => 'Repair Together sync']);
        $output = Artisan::output();

        $this->assertSame(0, $exit, $output);
        $this->assertMatchesRegularExpression('/^\d+\|\S{40,}$/m', $output);
        preg_match('/^(\d+\|\S{40,})$/m', $output, $m);

        $this->assertSame(['Repair Together sync'], $user->tokens()->pluck('name')->all());

        $this->getJson('/api/v2/users/me/language', ['Authorization' => 'Bearer '.$m[1]])
            ->assertOk();
    }

    public function testUnknownEmailFails(): void
    {
        $this->artisan('user:token', ['email' => 'nobody@example.com', 'name' => 'Anything'])
            ->expectsOutputToContain('nobody@example.com not found')
            ->assertExitCode(1);
    }

    public function testRefusesTheNameTheSiteUsesForLogins(): void
    {
        $user = User::factory()->restarter()->create(['email' => 'spa@example.com']);

        $this->artisan('user:token', ['email' => 'spa@example.com', 'name' => 'spa'])
            ->assertExitCode(1);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function testWarnsThatWritesNeedConsent(): void
    {
        User::factory()->restarter()->create([
            'email' => 'noconsent@example.com',
            'consent_future_data' => null,
        ]);

        $this->artisan('user:token', ['email' => 'noconsent@example.com', 'name' => 'Reader'])
            ->expectsOutputToContain('consent')
            ->assertExitCode(0);
    }
}
