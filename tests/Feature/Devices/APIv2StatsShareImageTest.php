<?php

namespace Tests\Feature\Devices;

use Tests\TestCase;

class APIv2StatsShareImageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testServesAnAllowlistedImageWithCorsAndImagePngContentType(): void
    {
        $response = $this->get('/api/v2/stats/share-image/ImpactRange1Landscape-1.png');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'image/png');
        // config/cors.php scopes CORS to 'api/*' precisely so this endpoint
        // - unlike the plain public/images/stats/ static file it proxies -
        // can be drawn onto a <canvas> cross-origin without tainting it.
        $response->assertHeader('Access-Control-Allow-Origin', '*');

        $expected = file_get_contents(public_path('images/stats/ImpactRange1Landscape-1.png'));
        $this->assertEquals($expected, $response->getContent());
    }

    public function testServesTheWavyDividerDecorationImage(): void
    {
        $response = $this->get('/api/v2/stats/share-image/WavyDividerLine.png');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'image/png');
    }

    // Every filename useStatsShareImage.js's getImage() can actually
    // produce - one per RANGES row/orientation - should serve successfully.
    // Spot-checks across the table rather than all 266 for test runtime.
    public function testServesEveryLevelAndOrientationInTheLookupTable(): void
    {
        $filenames = [
            'ImpactRange1Square-1.png',
            'ImpactRange2Landscape-60.png',
            'ImpactRange3Square-2.png',
            'ImpactRange4Landscape-9.png',
            'ImpactRange5Square-16.png',
            'ImpactRange6Landscape-64.png',
        ];

        foreach ($filenames as $filename) {
            $response = $this->get("/api/v2/stats/share-image/{$filename}");
            $response->assertSuccessful();
        }
    }

    public function testUnknownButAllowlistShapedFilenameReturns404(): void
    {
        // Matches the allowlist pattern but no such file exists on disk.
        $response = $this->getJson('/api/v2/stats/share-image/ImpactRange1Landscape-999999.png');
        $response->assertStatus(404);
    }

    /**
     * @dataProvider pathTraversalAttempts
     */
    public function testPathTraversalAttemptsAreRejected(string $filename): void
    {
        $response = $this->getJson('/api/v2/stats/share-image/'.$filename);
        $response->assertStatus(404);
    }

    public function pathTraversalAttempts(): array
    {
        return [
            'dotdot to .env' => ['../../.env'],
            'dotdot to .env, no leading slash needed' => ['../.env'],
            'url-encoded dotdot' => ['..%2f..%2f.env'],
            'double-encoded dotdot' => ['..%252f..%252f.env'],
            'absolute path' => ['/etc/passwd'],
            'null byte' => ["ImpactRange1Landscape-1.png\0.jpg"],
            'wrong extension' => ['ImpactRange1Landscape-1.php'],
            'directory traversal into another public asset' => ['../../.gitignore'],
            'not even close to the pattern' => ['hack.png'],
            'empty' => [''],
        ];
    }
}
