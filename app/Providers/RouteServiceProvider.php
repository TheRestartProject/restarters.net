<?php

namespace App\Providers;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/dashboard';



    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
             ->middleware('api')
             ->group(base_path('routes/api.php'));
    }

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // Many API routes (e.g. devices) check api_token in the controller rather than via auth:api, so the
            // default guard knows nothing about the user here.  Look them up via the token guards (Sanctum for the
            // Nuxt client, api_token for the rest) so that each user gets their own limit.
            $user = $request->user('sanctum') ?? $request->user('api');

            if ($user) {
                return Limit::perMinute(300)->by('user:' . $user->id);
            }

            // On Fly, X-Forwarded-For ends with Fly's edge IP, so $request->ip() is the same for every visitor and
            // one busy client would use up the limit for everyone.  Fly-Client-IP is set by Fly to the real client.
            return Limit::perMinute(300)->by('ip:' . ($request->header('Fly-Client-IP') ?: $request->ip()));
        });

        // Login/register/password endpoints. Env-configurable so test runs
        // (Playwright bursts) can relax it without editing source files.
        // Per real client (Fly-Client-IP, as above) and email address, so the
        // volunteers at a venue, sharing its wifi, don't share one limit; and
        // a looser limit per client, so trying one password across many
        // accounts is limited too.
        RateLimiter::for('auth', function (Request $request) {
            $perMinute = (int) config('restarters.auth_rate_limit');
            $client = $request->header('Fly-Client-IP') ?: $request->ip();
            $email = $request->input('email');
            $email = is_string($email) ? strtolower(trim($email)) : '';

            return [
                Limit::perMinute($perMinute)->by('auth:'.$client.'|'.$email),
                Limit::perMinute($perMinute * 3)->by('auth-client:'.$client),
            ];
        });

        $this->routes(function () {
            $this->mapApiRoutes();
            $this->mapWebRoutes();
        });
    }
}
