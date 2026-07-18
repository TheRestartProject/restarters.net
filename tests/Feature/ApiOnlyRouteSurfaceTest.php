<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * F4: pins the post-cutover web-route surface. Laravel serves the API plus
 * exactly the small web surface below (SSO bridge, deep-link redirectors,
 * anonymous exports/calendars, embeddable widgets, preview-deploy, and the
 * SPA catch-all). If this test fails you either added a web route that
 * belongs in the SPA/API, or removed something partners still iframe —
 * either way it should be a deliberate edit here, not an accident.
 */
class ApiOnlyRouteSurfaceTest extends TestCase
{
    private const ALLOWED_WEB_ROUTES = [
        'GET auth/bridge',
        'GET user/reset',
        'GET user/recover',
        'GET user/register/{hash?}',
        'GET group/accept-invite/{id}/{hash}',
        'GET party/accept-invite/{id}/{hash}',
        'GET party/invite/{code}',
        'GET group/invite/{code}',
        'GET export/devices/event/{id}',
        'GET export/devices/group/{id}',
        'GET export/devices',
        'GET export/groups/{id}/events',
        'GET export/networks/{id}/events',
        'GET calendar/user/{calendar_hash}',
        'GET calendar/group/{group}',
        'GET calendar/network/{network}',
        'GET calendar/group-area/{area}',
        'GET calendar/all-events/{hash_env}',
        'GET outbound/info/{type}/{id}/{format?}',
        'GET group/stats/{id}/{format?}',
        'GET admin/stats/1',
        'GET admin/stats/2',
        'GET party/stats/{id}/wide',
        'GET admin/preview-deploy',
        'POST admin/preview-deploy',
        'GET {any?}',
    ];

    public function testWebRouteSurfaceIsPinned(): void
    {
        $webRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(function ($route) {
                // Pin only the routes routes/web.php owns: web-middleware
                // routes whose action is a Closure or one of OUR controllers.
                // Vendor/package routes (Croppa image handler, translation
                // manager, debugbar, dusk, ignition, sanctum) are out of
                // scope — their controllers live outside App\.
                if (! in_array('web', $route->gatherMiddleware(), true)) {
                    return false;
                }
                $action = $route->getActionName();

                return $action === 'Closure' || str_starts_with($action, 'App\\');
            })
            ->flatMap(function ($route) {
                return collect($route->methods())
                    ->reject(fn ($m) => in_array($m, ['HEAD', 'OPTIONS'], true))
                    ->map(fn ($m) => $m.' '.$route->uri());
            })
            ->sort()
            ->values()
            ->all();

        $expected = collect(self::ALLOWED_WEB_ROUTES)->sort()->values()->all();

        $this->assertEquals($expected, $webRoutes);
    }

    public function testCatchAllRedirectsBrowsersToSpaPreservingPathAndQuery(): void
    {
        $response = $this->get('/some/legacy/bookmark?with=query');

        $response->assertRedirect(
            rtrim(config('restarters.frontend_url'), '/').'/some/legacy/bookmark?with=query'
        );
    }

    public function testCatchAllDoesNotSwallowUnknownApiPaths(): void
    {
        // The catch-all's `^(?!api/)` guard must let unknown API paths 404 as
        // JSON for machine clients instead of 302-redirecting to the SPA. Use
        // a non-v2 api path so the base TestCase's OpenAPI response validation
        // (which only fires for /api/v2) doesn't intercept a deliberately
        // unknown endpoint.
        $response = $this->withExceptionHandling()->get('/api/no-such-endpoint');

        $response->assertNotFound();
    }
}
