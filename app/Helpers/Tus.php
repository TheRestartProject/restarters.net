<?php

namespace App\Helpers;

use TusPhp\Cache\Cacheable;
use TusPhp\Cache\FileStore;
use TusPhp\Tus\Server;

class Tus
{
    /**
     * Directory where in-progress and completed tus uploads are assembled.
     */
    public static function uploadDir(): string
    {
        return storage_path('app/tus');
    }

    /**
     * Directory where the tus-php file cache (upload metadata) is kept.
     */
    public static function cacheDir(): string
    {
        return storage_path('app/tus-cache/');
    }

    /**
     * Build the tus-php file cache directly, WITHOUT constructing a Server/Request.
     *
     * TusPhp\Tus\Server's constructor eagerly builds a TusPhp\Request, which calls
     * Symfony's HttpRequest::createFromGlobals() - that reads PHP's real superglobals
     * unconditionally, which blows up under PHPUnit's CLI SAPI (no $_SERVER['REQUEST_METHOD']
     * etc.) once error_reporting is turned up to catch undefined-variable notices as
     * errors. Callers that only need to read/write the cache (e.g. updateMyPhotov2()
     * looking up a completed upload by key) should use this instead of buildServer()
     * so they don't pay for - or crash on - a Request they never use.
     *
     * The cache key prefix MUST match what Server actually uses: AbstractTus::setCache()
     * prefixes every key with "tus:" . strtolower(static::class) . ":" (i.e. "tus:server:"
     * for TusPhp\Tus\Server) - a bare `new FileStore()` defaults to just "tus:" and would
     * silently miss every entry the real Server wrote.
     */
    public static function buildCache(): Cacheable
    {
        $cacheDir = self::cacheDir();

        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $cache = new FileStore($cacheDir);
        $cache->setPrefix('tus:server:');

        return $cache;
    }

    /**
     * Build a tus-php Server configured identically wherever it's used (the
     * TusController that serves the protocol, and the API controller that
     * later looks up a completed upload by key). Using a shared factory keeps
     * the upload dir/cache dir/api path in sync between the two call sites.
     *
     * Only call this where a real Request is available/expected (i.e. the actual tus
     * protocol route) - see buildCache() for cache-only access.
     */
    public static function buildServer(): Server
    {
        $uploadDir = self::uploadDir();

        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $server = new Server(self::buildCache());
        $server->setUploadDir($uploadDir);
        $server->setApiPath('/api/tus');

        // This route is intentionally unauthenticated (see TusController), so cap what an
        // anonymous client can push to disk here even though updateMyPhotov2() separately
        // enforces its own 2MB limit once a completed upload is claimed. A little headroom
        // over the app-level limit avoids rejecting legitimate uploads before compression.
        $server->setMaxUploadSize(10 * 1024 * 1024);

        return $server;
    }
}
