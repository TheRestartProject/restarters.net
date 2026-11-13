<?php

namespace Tests\Feature\Networks;

use App\Helpers\Tus;
use App\Network;
use App\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use TusPhp\Cache\Cacheable;

/**
 * POST /api/v2/networks/{id}/logo — the API replacement for the old
 * PUT /networks/{network} network_logo upload (NetworkController::uploadLogov2).
 */
class APIv2NetworkLogoTest extends TestCase
{
    /** @var string[] */
    private array $tmpTusFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public_uploads');
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpTusFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        parent::tearDown();
    }

    private function seedCompletedTusUpload(string $sourcePath): string
    {
        $key = 'nl-'.uniqid();

        $uploadDir = Tus::uploadDir();
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $destPath = $uploadDir.'/'.$key;
        copy($sourcePath, $destPath);
        $this->tmpTusFiles[] = $destPath;

        $size = filesize($destPath);
        $cache = Tus::buildCache();
        $now = new \DateTime();

        $cache->set($key, [
            'name' => $key,
            'size' => $size,
            'offset' => $size,
            'checksum' => null,
            'location' => 'http://localhost/api/tus/'.$key,
            'file_path' => $destPath,
            'metadata' => [],
            'upload_type' => 'normal',
            'created_at' => $now->format(Cacheable::RFC_7231),
            'expires_at' => $now->modify('+1 day')->format(Cacheable::RFC_7231),
        ]);

        return $key;
    }

    private function uploadAs(User $user, int $networkId, ?string $key = null): \Illuminate\Testing\TestResponse
    {
        $key = $key ?? $this->seedCompletedTusUpload(public_path().'/images/community.jpg');
        $this->actingAs($user);

        return $this->postJson('/api/v2/networks/'.$networkId.'/logo?api_token='.$user->api_token, [
            'upload_key' => $key,
        ]);
    }

    public function testRequiresAuthentication(): void
    {
        $network = Network::factory()->create();

        $response = $this->withExceptionHandling()
                         ->postJson('/api/v2/networks/'.$network->id.'/logo', ['upload_key' => 'whatever']);

        $response->assertStatus(401);
    }

    public function testDeniedForUnrelatedUser(): void
    {
        $network = Network::factory()->create();
        $restarter = User::factory()->restarter()->create(['api_token' => 'nl-restarter']);

        $response = $this->uploadAs($restarter, $network->id);

        $response->assertStatus(403);
        $this->assertNull($network->fresh()->logo);
    }

    public function testAdministratorCanUploadLogo(): void
    {
        $network = Network::factory()->create();
        $admin = User::factory()->administrator()->create(['api_token' => 'nl-admin']);

        $response = $this->uploadAs($admin, $network->id);

        $response->assertSuccessful();
        $logo = $network->fresh()->logo;
        $this->assertNotNull($logo);
        $this->assertStringStartsWith('network_logos/', $logo);
        $this->assertEquals($logo, $response->json('data.logo'));
        // The upload and its _x100 sized variant are both stored.
        Storage::disk('public_uploads')->assertExists($logo);
        Storage::disk('public_uploads')->assertExists(preg_replace('/\.([^.\s]{3,4})$/', '-_x100.$1', $logo));
    }

    public function testCoordinatorCanUploadLogo(): void
    {
        $network = Network::factory()->create();
        $coordinator = User::factory()->networkCoordinator()->create(['api_token' => 'nl-coord']);
        $network->addCoordinator($coordinator);

        $response = $this->uploadAs($coordinator, $network->id);

        $response->assertSuccessful();
        $this->assertNotNull($network->fresh()->logo);
    }

    public function testRejectedWhenUploadsDisabled(): void
    {
        // The base TestCase disables exception handling; re-enable it so the
        // ValidationException surfaces as a 422 response, not a raised error.
        $this->withExceptionHandling();

        config(['restarters.features.image_upload' => false]);

        $network = Network::factory()->create();
        $admin = User::factory()->administrator()->create(['api_token' => 'nl-admin-disabled']);

        $response = $this->uploadAs($admin, $network->id);

        $response->assertStatus(422);
        $this->assertNull($network->fresh()->logo);
    }
}
