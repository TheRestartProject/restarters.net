<?php

namespace Tests\Feature\Admin;

use App\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * GET/POST /api/v2/admin/preview-deploys - the admin PR-preview-deploy tooling,
 * moved off the Blade admin/preview-deploy web route (which couldn't
 * authenticate the post-cutover SPA admin). GitHub calls are faked.
 */
class APIv2PreviewDeployTest extends TestCase
{
    public function testListForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'pd-tok']);
        $this->actingAs($user);

        $this->getJson('/api/v2/admin/preview-deploys?api_token=pd-tok')->assertStatus(403);
    }

    public function testDeployForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'pd-tok']);
        $this->actingAs($user);

        $this->postJson('/api/v2/admin/preview-deploys?api_token=pd-tok', ['branch' => 'x'])->assertStatus(403);
    }

    public function testListReturnsOpenPrsForAdmin(): void
    {
        Http::fake([
            'api.github.com/repos/*/pulls*' => Http::response([
                ['number' => 42, 'title' => 'My PR', 'head' => ['ref' => 'my-branch'], 'user' => ['login' => 'edwh']],
            ], 200),
        ]);
        config(['services.github.deploy_pat' => 'fake-pat']);

        $admin = User::factory()->administrator()->create(['api_token' => 'pd-tok']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/admin/preview-deploys?api_token=pd-tok');

        $response->assertSuccessful();
        $response->assertJsonPath('data.prs.0.number', 42);
        $response->assertJsonPath('data.prs.0.branch', 'my-branch');
        $response->assertJsonPath('data.prs.0.author', 'edwh');
        $this->assertNull($response->json('data.error'));
    }

    public function testListReportsMissingPatGracefully(): void
    {
        config(['services.github.deploy_pat' => null]);

        $admin = User::factory()->administrator()->create(['api_token' => 'pd-tok']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/admin/preview-deploys?api_token=pd-tok');

        $response->assertSuccessful();
        $this->assertSame([], $response->json('data.prs'));
        $this->assertStringContainsString('GITHUB_DEPLOY_PAT', $response->json('data.error'));
    }

    public function testDeployTriggersTheWorkflow(): void
    {
        Http::fake([
            'api.github.com/repos/*/actions/workflows/*/dispatches' => Http::response('', 204),
        ]);
        config(['services.github.deploy_pat' => 'fake-pat']);

        $admin = User::factory()->administrator()->create(['api_token' => 'pd-tok']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/admin/preview-deploys?api_token=pd-tok', ['branch' => 'my-branch']);

        $response->assertSuccessful();
        $this->assertStringContainsString('my-branch', $response->json('data.message'));
        Http::assertSent(fn ($req) => str_contains($req->url(), '/dispatches') && $req['inputs']['branch'] === 'my-branch');
    }

    public function testDeployRequiresABranch(): void
    {
        config(['services.github.deploy_pat' => 'fake-pat']);
        $admin = User::factory()->administrator()->create(['api_token' => 'pd-tok']);
        $this->actingAs($admin);

        $this->withExceptionHandling()
             ->postJson('/api/v2/admin/preview-deploys?api_token=pd-tok', [])
             ->assertStatus(422);
    }
}
