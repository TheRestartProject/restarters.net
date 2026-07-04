<?php

namespace Tests\Feature\Users;

use App\Helpers\Tus;
use App\User;
use Tests\TestCase;

/**
 * End-to-end coverage of the real tus route (routes/api.php -> TusController::serve()
 * -> TusPhp\Tus\Server), as distinct from APIv2UserPhotoTest (which covers
 * updateMyPhotov2() from the point a tus upload has already completed).
 *
 * IMPORTANT LIMITATION: the tus protocol's byte-transfer step (the PATCH request that
 * actually streams file content) is hard-coded by tus-php to read the request body from
 * php://input (see TusPhp\File::getInputStream()/upload()) - real PHP-SAPI-only stdin.
 * Laravel's HTTP test client (TestCase::call()) builds a Symfony Request object in
 * memory and dispatches it straight through the kernel; it never touches PHP's actual
 * superglobals or stdin (see Illuminate\Foundation\Testing\Concerns\MakesHttpRequests::call()
 * and Symfony\Component\HttpFoundation\Request::create()), so there is no way to feed
 * tus-php's PATCH handler a fake request body from inside a single PHPUnit process
 * without a real HTTP server in front of it.
 *
 * What CAN be driven for real is the upload-creation POST: tus-php's Request class
 * reads from PHP's real superglobals (HttpRequest::createFromGlobals(), see
 * TusPhp\Request::__construct()) rather than from Laravel's request object, and
 * handlePost() only inspects headers (Upload-Length/Upload-Metadata) - no body read. So
 * this test manually seeds $_SERVER with the real tus headers (restored in tearDown())
 * and then drives the actual /api/tus route through the normal Laravel test client. That
 * exercises the real routing table, the real TusController, and the real
 * TusPhp\Tus\Server::handlePost() - genuinely proving a client can create an upload
 * against this route and get back a Location/Tus-Resumable response with a valid key.
 *
 * The subsequent "file finished uploading, now attach it to my profile" behaviour is
 * covered thoroughly (success, validation, size/type limits, replay protection, PII,
 * cross-user isolation) by APIv2UserPhotoTest, which seeds a completed upload directly
 * into the same Tus::buildServer() cache/upload-dir the real PATCH handler would have
 * left behind - so updateMyPhotov2() itself runs completely unmocked in both suites.
 */
class APIv2TusUploadTest extends TestCase
{
    private array $originalServer = [];
    private $originalGet;
    private $originalPost;
    private $originalCookie;
    private $originalFiles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET ?? null;
        $this->originalPost = $_POST ?? null;
        $this->originalCookie = $_COOKIE ?? null;
        $this->originalFiles = $_FILES ?? null;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_COOKIE = $this->originalCookie;
        $_FILES = $this->originalFiles;
        parent::tearDown();
    }

    /**
     * TusPhp\Request reads PHP's REAL $_SERVER (via HttpRequest::createFromGlobals()),
     * not the Symfony Request object Laravel's test client builds - so headers must be
     * seeded into both places to get a consistent picture: real $_SERVER for tus-php's
     * own parsing, and transformHeadersToServerVars() for the Laravel routing/middleware
     * stack that dispatches the request to TusController in the first place.
     */
    private function seedTusServerGlobals(string $path, array $headers): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['PATH_INFO'] = $path;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;

        // TusPhp\Request::__construct() calls Symfony's HttpRequest::createFromGlobals(),
        // which unconditionally reads $_GET/$_POST/$_COOKIE/$_FILES/$_SERVER - none of
        // which PHP's CLI SAPI populates for a simulated PHPUnit request. With this
        // worktree's elevated error_reporting (catches undefined-variable notices as
        // errors - see phpunit.xml/-d error_reporting=8191), reading an unset superglobal
        // is fatal, so all of them must exist (even if empty) before dispatch.
        $_GET = $_GET ?? [];
        $_POST = $_POST ?? [];
        $_COOKIE = $_COOKIE ?? [];
        $_FILES = $_FILES ?? [];

        foreach ($headers as $key => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }
    }

    public function testCreatingAnUploadThroughTheRealTusRouteReturnsALocationAndKey(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tus-tok-1']);
        $this->actingAs($user);

        $filename = base64_encode('phpunit-tus-upload.jpg');
        $fileSize = 12345;
        $headers = [
            'Tus-Resumable' => '1.0.0',
            'Upload-Length' => (string) $fileSize,
            'Upload-Metadata' => 'filename ' . $filename,
        ];

        $this->seedTusServerGlobals('/api/tus', $headers);

        $response = $this->call(
            'POST',
            '/api/tus',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers)
        );

        $response->assertStatus(201);
        $response->assertHeader('Tus-Resumable');
        $this->assertNotEmpty($response->headers->get('Location'));

        // The key is the last path segment of the Location header.
        $location = $response->headers->get('Location');
        $key = substr($location, strrpos($location, '/') + 1);
        $this->assertNotEmpty($key);

        // The upload should now exist in the shared tus cache (same one updateMyPhotov2
        // reads from) - offset 0, not yet complete.
        $meta = Tus::buildCache()->get($key);
        $this->assertNotNull($meta);
        $this->assertEquals(0, $meta['offset']);
        $this->assertEquals($fileSize, $meta['size']);

        // Cleanup: remove the cache entry/file this real request created.
        Tus::buildCache()->delete($key);
        if (! empty($meta['file_path'])) {
            @unlink($meta['file_path']);
        }
    }

    public function testTusRouteRequiresNoAuthToken(): void
    {
        // The tus route is deliberately NOT behind auth:api (see TusController docblock) -
        // confirm an unauthenticated POST still gets a normal tus response, not a 401.
        $filename = base64_encode('anon-upload.jpg');
        $headers = [
            'Tus-Resumable' => '1.0.0',
            'Upload-Length' => '10',
            'Upload-Metadata' => 'filename ' . $filename,
        ];

        $this->seedTusServerGlobals('/api/tus', $headers);

        $response = $this->call(
            'POST',
            '/api/tus',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers)
        );

        $response->assertStatus(201);

        $location = $response->headers->get('Location');
        $key = substr($location, strrpos($location, '/') + 1);
        $meta = Tus::buildCache()->get($key);

        Tus::buildCache()->delete($key);
        if (! empty($meta['file_path'] ?? null)) {
            @unlink($meta['file_path']);
        }
    }
}
